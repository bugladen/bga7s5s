<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05TimebombFlipped extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "The Porcelain Heart";
        $this->Title = "Syrneth Artifact";
        $this->Image = "05TimebombFlipped.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 26;

        $this->IsDoubleSided = true;
        $this->FlippedImage = "05Timebomb.v5.jpg";

        $this->initializeFaction("Ussura");

        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 1;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            "Artifact",
            "Syrneth",
            "Unique"
        ];

        $this->Text = "<p>This card cannot be moved, destroyed, or sent to <b>The Locker</b>.</p>
<p>When this card is revealed its owner takes control of it and equips it to their <b>Leader</b>.</p>
<p><b>Forced:</b> When the equipped character's adversary announces a combat card, it has -1[Parry].</p>";

        $this->resetCard();
    }

    // WHY: Printed "cannot be moved" — Kaspar's Panzerhand / Syrneth Hand shape.
    // Take-control steal flows filter with canBeMoved(); dying-host unequip is separate.
    public function canBeMoved(): bool
    {
        return false;
    }

    // WHY: Always equips to the owner's Leader (Object of Wonder _01202). Flip path and
    // any later equip attempt must land on Leader, not an arbitrary originalTargetId.
    public function getRequiredAttachTargetId(Theah $theah, int $originalTargetId): int
    {
        $character = $theah->getCharacterById($originalTargetId);
        if ($character === null)
        {
            throw new UserException("You must equip this card to your Leader, and you have no Leader in play.");
        }

        $leader = $theah->getLeaderByPlayerId($character->ControllerId);
        $playerLockerName = $theah->game->getPlayerLockerName($character->ControllerId);

        if (! $leader || $leader->Location == $playerLockerName)
        {
            throw new UserException("You must equip this card to your Leader, and you have no Leader in play.");
        }

        return $leader->Id;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        // WHY: Same Leader gate as Object of Wonder when someone tries to equip elsewhere.
        if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
        {
            $leader = $event->theah->getLeaderByPlayerId($event->playerId);
            $playerLockerName = $event->theah->game->getPlayerLockerName($event->playerId);

            if (! $leader || $leader->Location == $playerLockerName)
            {
                throw new UserException("You must equip this card to your Leader, and you have no Leader in play.");
            }

            if ($event->characterId != $leader->Id)
            {
                throw new UserException("The Porcelain Heart must equip to your Leader.");
            }
        }

        // WHY: Syrneth Hand _01204 — "cannot be destroyed" is an unequip veto unless the
        // host is dying. Blocking unequip on Leader death softlocks the destroy chain
        // (unEquipAllAttachments → destroy). Steal/move is already gated by canBeMoved().
        if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
        {
            $owner = $this->attachedTo($event->theah);
            $canDiscard = $owner instanceof Character && $owner->IsDying;

            if (! $canDiscard)
            {
                throw new UserException("The Porcelain Heart cannot be destroyed or moved from the equipped character.");
            }
        }

        // WHY: Printed "cannot be sent to The Locker" is separate from destroy/unequip.
        // Object of Wonder uses createCardSentToLockerEvent on attachments — veto that path.
        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            throw new UserException("The Porcelain Heart cannot be sent to The Locker.");
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Forced — always-on while attached. Adversary of the equipped character
        // announces → actorId is that adversary → adversaryId == AttachedToId.
        // Apply on EventDuelCalculateCombatCardStats (Syrneth Hand Technique_01204 /
        // So It Begins _01183), not CombatCardAnnounced (BoyBand Reaction picker).
        // removeParry allows negatives so later +Parry techniques still net correctly.
        if ($event instanceof EventDuelCalculateCombatCardStats && $this->isAttached())
        {
            if ($event->adversaryId == $this->AttachedToId)
            {
                $event->explanations[] = sprintf(
                    "%s: Equipped character's adversary announces a combat card — -1 Parry.",
                    $this->getInjectCode()
                );
                $event->removeParry(1);

                // WHY Maneuver column: So It Begins / Technique_01204 — Combat Card column
                // only stores card ids. Skip when Parry is dashed. Plain Name matches
                // other column labels (CAD GaryFlipped — no translate).
                if (! $event->dashedParry)
                {
                    $note = sprintf("%s: -1 Parry to combat card", $this->Name);
                    $event->theah->recordDuelRoundColumnNote('maneuver', 'note_' . $this->Id, $note);
                }
            }
        }
    }
}
