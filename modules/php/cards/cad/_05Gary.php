<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Gary;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Gary extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Rena Klingenhalter";
        $this->Title = "Collected";
        $this->Image = "05Gary.v3.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 3;

        $this->initializeFaction("Eisen");

        $this->Resolve = 10;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->CrewCap = 5;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Hero",
            "Hunter",
            "Eisen"
        ];

        $this->Text = "<p>During setup, reveal an attachment from your deck and equip it to Rena, at -1 cost.</p>
<p>Reaction: When a character at this location would suffer a wound • Wound Rena instead</p>
<p>Forced: When Rena has four or more wounds • Flip her.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05Gary(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventTableSetup)
        {
            $game = $event->theah->game;
            $affordable = $this->getAffordableSetupAttachments($game);
            if (count($affordable) == 0)
            {
                // WHY: Hands are empty at SETUP_TABLE (panache draw is Planning). With
                // setup −1 only WealthCost ≤ 1 attachments are free. Nothing affordable → skip.
                $game->notify->all("message", '${rena_inject_code}: ${player_name} has no affordable attachment in their Faction Deck to equip during setup.', [
                    'rena_inject_code' => $this->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($this->ControllerId),
                ]);
                return;
            }

            $game->notify->all("message", '${rena_inject_code}: ${player_name} is choosing an attachment from their Faction Deck to reveal and equip to Rena at -1 cost.', [
                'rena_inject_code' => $this->getInjectCode(),
                'player_name' => $game->getPlayerNameById($this->ControllerId),
            ]);

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "05Gary");
            $event->theah->queueEvent($transition);
        }

        // WHY: Forced flip after wounds land. parent::handleEvent already updated $this->Wounds.
        if ($event instanceof EventCharacterWounded && $event->characterId == $this->Id)
        {
            if ($this->Wounds >= 4 && ! $event->theah->game->characterIsInDiscardOrLocker($this))
            {
                $this->flipToBSide($event->theah);
            }
        }
    }

    /**
     * Replace A-side with B-side (_05GaryFlipped) keeping the same card Id.
     * WHY same Id: leader_card_id / AttachedToId / duel markers all key on numeric Id.
     * WHY not destroy-recreate: EventHub destroy intentionally wipes wounds/attachments.
     */
    public function flipToBSide(Theah $theah): void
    {
        $game = $theah->game;

        $controllerId = $this->ControllerId;
        $ownerId = $this->OwnerId;
        $location = $this->Location;
        $engaged = $this->Engaged;
        $wounds = $this->Wounds;
        $woundsHealedIncoming = $this->WoundsHealedIncoming;
        $isDying = $this->IsDying;
        $reknown = $this->Reknown;
        $attachments = $this->Attachments;
        $conditions = $this->Conditions;
        $faceDown = $this->FaceDown;

        $aTraits = $this->Traits;
        $aModifiedTraits = empty($this->ModifiedTraits) ? $this->Traits : $this->ModifiedTraits;

        $combatDelta = $this->ModifiedCombat - $this->Combat;
        $finesseDelta = $this->ModifiedFinesse - $this->Finesse;
        $influenceDelta = $this->ModifiedInfluence - $this->Influence;
        $resolveDelta = $this->ModifiedResolve - $this->Resolve;
        $panacheDelta = $this->ModifiedPanache - $this->Panache;
        $crewCapDelta = $this->ModifiedCrewCap - $this->CrewCap;

        $b = $game->instantiateCard('05GaryFlipped', $this->Id);
        if (! ($b instanceof _05GaryFlipped))
        {
            return;
        }

        $b->ControllerId = $controllerId;
        $b->OwnerId = $ownerId;
        $b->Location = $location;
        $b->Engaged = $engaged;
        $b->Wounds = $wounds;
        $b->WoundsHealedIncoming = $woundsHealedIncoming;
        $b->IsDying = $isDying;
        $b->Reknown = $reknown;
        $b->Attachments = $attachments;
        $b->Conditions = $conditions;
        $b->FaceDown = $faceDown;

        // WHY: Start from B printed Modified*, then re-apply A-side deltas so attachment
        // and scheme modifiers survive the base-stat change (Combat 2→3, Influence 2→1, etc.).
        $b->ModifiedCombat = $b->Combat + $combatDelta;
        $b->ModifiedFinesse = $b->Finesse + $finesseDelta;
        $b->ModifiedInfluence = $b->Influence + $influenceDelta;
        $b->ModifiedResolve = $b->Resolve + $resolveDelta;
        $b->ModifiedPanache = $b->Panache + $panacheDelta;
        $b->ModifiedCrewCap = $b->CrewCap + $crewCapDelta;

        // WHY: Merge B printed traits (adds Berserker) with any traits A had gained beyond print.
        $gained = array_values(array_diff($aModifiedTraits, $aTraits));
        $b->ModifiedTraits = array_values(array_unique(array_merge($b->Traits, $gained)));

        $theah->addCardToWorld($b);
        $b->IsUpdated = true;

        // WHY: Keep card_type aligned with serialized class for getCardIdByType lookups.
        $game->DbQuery("UPDATE card SET card_type = '05GaryFlipped' WHERE card_id = {$b->Id}");

        $game->notify->all("characterFlipped", '${rena_inject_code}: ${player_name}\'s Rena flips to her Wrathful side!', [
            'rena_inject_code' => $b->getInjectCode(),
            'player_name' => $game->getPlayerNameById($controllerId),
            'card' => $b->getPropertyArray($game),
            'characterId' => $b->Id,
        ]);

        if ($panacheDelta != 0 || $b->ModifiedPanache != $this->ModifiedPanache)
        {
            $game->notify->all("panacheModified", '', [
                "playerId" => $controllerId,
                "panache" => $b->ModifiedPanache,
            ]);
        }
    }

    /**
     * @return Attachment[]
     */
    private function getAffordableSetupAttachments(Game $game): array
    {
        $deck = $game->getGameDeckObject();
        $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
        $cards = $deck->getCardsInLocation($deckName);
        $out = [];

        foreach ($cards as $cardObject)
        {
            $card = $game->theah->getCardById($cardObject['id']);
            if (! ($card instanceof Attachment) || $card->FakeAttachment)
            {
                continue;
            }

            // WHY: SETUP_TABLE is before Planning draw — hand is empty. Setup −1 means
            // only WealthCost ≤ 1 completes at cost 0. Higher-cost attachments cannot be paid.
            $cost = $card->WealthCost - 1;
            if ($cost < 0)
            {
                $cost = 0;
            }
            if ($cost == 0)
            {
                $out[] = $card;
            }
        }

        return $out;
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::SETUP_TABLE_05GARY)
        {
            $args['performerIds'] = $this->Id;
            $attachments = $this->getAffordableSetupAttachments($game);
            $args['attachments'] = array_map(fn($card) => $card->getPropertyArray($game), $attachments);
        }

        if ($state == States::SETUP_TABLE_05GARY_2)
        {
            $id = $game->globals->get(Game::CHOSEN_CARD);
            $card = $game->theah->getCardById($id);
            $args['card'] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::SETUP_TABLE_05GARY)
        {
            $affordable = $this->getAffordableSetupAttachments($game);
            if (count($affordable) > 0)
            {
                throw new UserException("There are affordable attachments in your Faction Deck.");
            }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::SETUP_TABLE_05GARY)
        {
            $attachment = $game->theah->getCardById($id);
            if (! ($attachment instanceof Attachment) || $attachment->FakeAttachment)
            {
                throw new UserException("Invalid attachment.");
            }

            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            if ($attachment->Location != $deckName)
            {
                throw new UserException("Card is not in your Faction Deck.");
            }

            $cost = $attachment->WealthCost - 1;
            if ($cost < 0)
            {
                $cost = 0;
            }
            if ($cost > 0)
            {
                // WHY: Empty hand at setup — only free-after-discount attachments are legal.
                throw new UserException("You cannot afford this attachment during setup.");
            }

            $discount = 1;
            $explanations = sprintf("%s: -1 during setup.", $this->getInjectCode());

            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $this->Id);

            $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $this->ControllerId);
            $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);

            $game->notify->all("message", '${player_name} revealed ${card_inject_code}.', [
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "card_inject_code" => $attachment->getInjectCode(),
            ]);

            $equipEvent = EventFactory::createAttachmentEquippedEvent(
                $this->ControllerId,
                $actualTargetId,
                $attachment->Id,
                $discount,
                $cost,
                $asAction = false,
                $explanations,
                false,
                $this->Id,
                (string) $this->Id
            );
            $game->theah->eventCheck($equipEvent);
            $game->theah->queueEvent($equipEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}
