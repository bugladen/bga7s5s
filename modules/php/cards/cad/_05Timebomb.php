<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Timebomb extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Klim Grabiezca";
        $this->Title = "Kill 'Em!";
        $this->Image = "05Timebomb.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 26;

        $this->IsDoubleSided = true;
        $this->FlippedImage = "05TimebombFlipped.v5.jpg";

        $this->initializeFaction("Ussura");

        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 0;

        $this->Traits = [
            "Antiquarian",
            "Scion",
            "Ussura"
        ];

        // WHY: Stub had Nevermore's scheme text pasted in. Art (PTv0.5 / CAD-US7A) is:
        // Approach ban + HD-end Home Forced wounds + destroy→flip.
        $this->Text = "<p>Klim Grabiezca cannot be in an Approach deck.</p>
<p><b>Forced:</b> At the end of High Drama, if Klim is at <b>Home</b> • Wound him and your <b>Leader</b> twice.</p>
<p><b>Forced:</b> When Klim would be destroyed • Flip him.</p>";

        $this->resetCard();
    }

    // WHY: Printed Approach ban — DeckValidator + PutIntoApproachDeck eventCheckCore
    // (Orel / Damned / Brute shape). Always false (structural deck rule, not Silence-blankable).
    public function canEnterApproachDeck(): bool
    {
        return false;
    }

    public function handleEvent(Event $event)
    {
        // WHY: runEventHubAfterCards=true on Destroy — card handlers run before the hub
        // locker recreate. Cancel + flip here so Klim never hits the locker. Theah already
        // skips handleEvent while Fate's Silence blanks him, so blanked Klim destroys normally
        // via handleCoreCharacterEvent → hub (no flip).
        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $event->canceled = true;
            $this->flipToPorcelainHeart($event->theah);
            return;
        }

        parent::handleEvent($event);

        // WHY: EventHighDramaPhaseEnd (Reaction_04043 / Equal Claim). "at Home" = his
        // Location is PLAYER_HOME — not cardInCity. Wound him twice then Leader twice
        // (printed order). abilityId = card Id so Cascade/Cooper treat this as an ability.
        if ($event instanceof EventHighDramaPhaseEnd)
        {
            if (! $this->isControlled())
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($this))
            {
                return;
            }

            if ($this->Location != Game::LOCATION_PLAYER_HOME)
            {
                return;
            }

            $game = $event->theah->game;
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);

            $game->notify->all("message", '${klim_inject_code}: At the end of High Drama while at Home — wound Klim and ${player_name}\'s Leader twice.', [
                'klim_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $woundKlim = EventFactory::createCharacterBeingWoundedEvent(
                $this->Id,
                $this->Id,
                2,
                $this->getInjectCode(),
                (string) $this->Id
            );
            $event->theah->queueEvent($woundKlim);

            if ($leader !== null && ! $game->characterIsInDiscardOrLocker($leader))
            {
                $woundLeader = EventFactory::createCharacterBeingWoundedEvent(
                    $leader->Id,
                    $this->Id,
                    2,
                    $this->getInjectCode(),
                    (string) $this->Id
                );
                $event->theah->queueEvent($woundLeader);
            }
        }
    }

    /**
     * Replace A-side Character with B-side FactionAttachment (Porcelain Heart), same card Id.
     * Owner takes control and equips it to their Leader (printed B-side reveal text).
     *
     * WHY same Id: AttachedToId / duel markers / DB card_id all key on numeric Id.
     * WHY not destroy-recreate: EventHub destroy intentionally wipes state and sends to locker.
     * WHY Character→Attachment (not Gary Leader→Leader): B-side is The Porcelain Heart.
     */
    public function flipToPorcelainHeart(Theah $theah): void
    {
        $game = $theah->game;

        $ownerId = $this->OwnerId;
        $controllerId = $this->ControllerId;
        $fromLocation = $this->Location;

        // WHY: Wound-threshold destroy already unequipped (IsDying). Direct destroy events
        // (Action_01019 etc.) may not — strip before the type swap so orphan hosts clear.
        if (! $this->IsDying)
        {
            $this->unEquipAllAttachments($theah);
        }

        $wasChallengeParticipant = $this->hasCondition(Game::DUEL_CHALLENGER)
            || $this->hasCondition(Game::DUEL_DEFENDER);
        $inDuel = (bool) $game->globals->get(Game::IN_DUEL, false);

        $aInject = $this->getInjectCode();

        $b = $game->instantiateCard('05TimebombFlipped', $this->Id);
        if (! ($b instanceof _05TimebombFlipped))
        {
            return;
        }

        // WHY: Printed "its owner takes control" — OwnerId, not current ControllerId
        // (opponent-controlled Klim still returns the Artifact to its owner).
        $b->OwnerId = $ownerId;
        $b->ControllerId = $ownerId;
        $b->Location = $fromLocation;
        $b->Engaged = false;
        $b->IsUpdated = true;

        $theah->addCardToWorld($b);

        // WHY: Keep card_type aligned with serialized class for getCardIdByType lookups (Gary).
        $game->DbQuery("UPDATE card SET card_type = '05TimebombFlipped' WHERE card_id = {$b->Id}");

        // WHY: cardRemovedFromPlay (not characterDestroyed) — destroy notif flies to locker
        // and pushes the card into the locker array. We only need the city/Home token gone
        // before attachmentEquipped recreates him on the Leader.
        $game->notify->all("cardRemovedFromPlay", '${klim_inject_code} flips to ${heart_inject_code} instead of being destroyed.', [
            'klim_inject_code' => $aInject,
            'heart_inject_code' => $b->getInjectCode(),
            'cardId' => $b->Id,
            'toLocation' => $fromLocation,
        ]);

        if ($wasChallengeParticipant && ! $inDuel)
        {
            $game->clearChallengeParticipantMarkers(
                (int) $game->globals->get(Game::CHOSEN_PERFORMER, 0),
                (int) $game->globals->get(Game::CHOSEN_TARGET, 0)
            );
        }

        $leader = $theah->getLeaderByPlayerId($ownerId);
        if ($leader === null || $game->characterIsInDiscardOrLocker($leader))
        {
            // WHY: Reveal still transfers control; without a Leader leave the Heart at owner's Home.
            $b->Location = Game::LOCATION_PLAYER_HOME;
            $game->moveCard($b->Id, Game::LOCATION_PLAYER_HOME, $ownerId, $b);
            $game->notify->all("message", '${heart_inject_code}: ${player_name} has no Leader in play — The Porcelain Heart stays at Home.', [
                'heart_inject_code' => $b->getInjectCode(),
                'player_name' => $game->getPlayerNameById($ownerId),
            ]);
            return;
        }

        $actualTargetId = $b->getRequiredAttachTargetId($theah, $leader->Id);

        $equipEvent = EventFactory::createAttachmentEquippedEvent(
            $ownerId,
            $actualTargetId,
            $b->Id,
            0,
            0,
            false,
            sprintf("%s: revealed — equip to Leader.", $b->getInjectCode()),
            false,
            $b->Id,
            (string) $b->Id
        );
        $theah->queueEvent($equipEvent);
    }
}
