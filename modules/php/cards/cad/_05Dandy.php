<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandyb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _05Dandy extends Leader implements IHasActions
{
    use ActionTrait;

    // WHY: Applied is how much Influence this passive is currently subtracting.
    // Unclamped is the last intended Influence, which may be negative. EventHub
    // clamps the chip at 0, so restoring from ModifiedInfluence would not give
    // the penalty back when Renown drops.
    public int $AppliedRenownPenalty = 0;
    public int $UnclampedInfluence = 0;
    // HOW much InfluenceModifier is already inside UnclampedInfluence.
    // Equip adds the modifier onto ModifiedInfluence and does not emit
    // EventCharacterInfluenceModified, so a later Renown sync would rebuild
    // the chip without it. Crystal Eye's +1 then cancels a Renown coming off:
    // the chip stays put and no log line is queued.
    public int $TrackedAttachmentInfluence = 0;
    // WHY: Éventail writes Unclamped in modifyInfluence before queueing
    // EventCharacterInfluenceModified. Dusk queues Engarde before CardMoved
    // home (Nazem needs enemies still at the site), so the home Renown sync
    // would rebuild from a stale Unclamped and wipe the en-garde +1; the
    // deferred InfMod then double-counts if we also apply it. Count how many
    // InfMod handlers should skip the Unclamped delta.
    public int $SkipExternalUnclampedInfluenceEvents = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Claude de la Roche";
        $this->Title = "Disgraced Dandy";
        $this->Image = "05Dandy.v1.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 14;

        $this->initializeFaction("Montaigne");

        $this->Resolve = 7;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 4;

        $this->CrewCap = 7;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Scoundrel",
            "Diplomat",
            "Merchant",
            "Montaigne"
        ];

        $this->Text = "<p>During setup, draw four cards. <i>(After shuffling)</i></p>
        <p>Claude has -1 [Influence] for each Renown at his location.</p>
        <p><b>Action:</b> Pay [1 Wealth] • Move Claude to any <b>City</b> location.</p>
        <p><b>Action:</b> Pay [2 Wealth] • Collect a Renown from this location.</p>";

        $this->resetCard();
        $this->UnclampedInfluence = $this->ModifiedInfluence;

        $this->Actions = [
            new Action_05Dandya(),
            new Action_05Dandyb(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterInfluenceModified && $event->CharacterId == $this->Id)
        {
            // Own penalty events already wrote Unclamped before they were queued.
            if ($event->Reason !== $this->getInjectCode())
            {
                if ($this->SkipExternalUnclampedInfluenceEvents > 0)
                {
                    $this->SkipExternalUnclampedInfluenceEvents--;
                    $this->IsUpdated = true;
                }
                else
                {
                    $this->UnclampedInfluence += ($event->NewInfluence - $event->OldInfluence);
                    $this->IsUpdated = true;
                }
            }
            return;
        }

        $this->foldAttachmentInfluence($event->theah, true);

        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        if ($event instanceof EventTableSetup && $this->ControllerId != 0)
        {
            $this->drawSetupCards($event->theah);
        }

        // WHY: This rewrites ModifiedInfluence from Renown at his location.
        // Pressures then read that chip. Do not hook EventPressureOccuring or
        // getInfluencePressureValue — those only change the pressure total.
        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            // WHY: CardMoved runs before Location is written. Read the destination.
            $this->syncRenownPenalty($event->theah, $event->toLocation);
        }

        if ($event instanceof EventCharacterMustered && $event->characterId == $this->Id)
        {
            // WHY: Same as a move — use the muster destination. He may still be
            // in the faction deck on this object when the event is handled.
            $this->syncRenownPenalty($event->theah, $event->location);
        }

        if ($event instanceof EventRenownAddedToLocation && $event->location == $this->Location)
        {
            $this->syncRenownPenalty($event->theah, $this->Location);
        }

        if ($event instanceof EventRenownRemovedFromLocation && $event->location == $this->Location)
        {
            $this->syncRenownPenalty($event->theah, $this->Location);
        }
    }

    public function onAbilitiesBlanked(Theah $theah): void
    {
        $this->syncRenownPenalty($theah, $this->Location);
    }

    public function onAbilitiesUnblanked(Theah $theah): void
    {
        if ($this->IsDying || $this->ControllerId == 0 || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        $this->syncRenownPenalty($theah, $this->Location);
    }

    private function drawSetupCards(Theah $theah): void
    {
        $game = $theah->game;
        $deck = $game->getGameDeckObject();
        $location = $game->getPlayerFactionDeckName($this->ControllerId);
        // WHY: buildDecks already shuffled the faction deck before SETUP_TABLE.
        // The parenthetical is that timing, not a second shuffle. Cap at the
        // deck size so an empty test deck does not draw from an empty discard.
        $available = count($deck->getCardsInLocation($location));
        $toDraw = min(4, $available);

        for ($i = 0; $i < $toDraw; $i++)
        {
            $draw = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $theah->queueEvent($draw);
        }
    }

    private function renownAt(Theah $theah, string $location): int
    {
        if ($location == Game::LOCATION_PLAYER_HOME || ! $theah->locationInCity($location))
        {
            return 0;
        }

        return max(0, $theah->getCityLocation($location)->Renown);
    }

    public function repairAttachmentInfluence(Theah $theah): void
    {
        $this->foldAttachmentInfluence($theah, true);
    }

    // WHY: Callers that bump ModifiedInfluence and queue InfMod (Éventail) must
    // also land the delta on Unclamped immediately — see SkipExternalUnclampedInfluenceEvents.
    public function absorbImmediateInfluenceDelta(int $delta): void
    {
        $this->UnclampedInfluence += $delta;
        $this->SkipExternalUnclampedInfluenceEvents++;
        $this->IsUpdated = true;
    }

    private function syncRenownPenalty(Theah $theah, string $location): void
    {
        if ($this->ControllerId == 0 || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        // Blanking calls sync without going through handleEvent.
        $this->foldAttachmentInfluence($theah, false);

        $desired = 0;
        if (! $this->abilitiesAreBlanked())
        {
            $desired = $this->renownAt($theah, $location);
        }

        if ($desired === $this->AppliedRenownPenalty)
        {
            return;
        }

        $newUnclamped = $this->UnclampedInfluence + $this->AppliedRenownPenalty - $desired;
        $oldChip = $this->ModifiedInfluence;
        // WHY: Write the chip now. A later move/renown event can run before this
        // queued modifier is processed; it must see the penalty already applied
        // or it will skip a restore that the stale event then paints back on.
        $reported = max(0, $newUnclamped);
        $this->UnclampedInfluence = $newUnclamped;
        $this->AppliedRenownPenalty = $desired;
        $this->ModifiedInfluence = $reported;
        $this->IsUpdated = true;

        if ($reported === $oldChip)
        {
            return;
        }

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $this->Id,
            $oldChip,
            $reported,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    // null: an Influence lock owns the chip. Do not rebuild over it.
    private function attachmentInfluenceSum(Theah $theah): ?int
    {
        $sum = 0;
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if (! $attachment instanceof Attachment)
            {
                continue;
            }
            if ($attachment->InfluenceLocked)
            {
                return null;
            }
            $sum += $attachment->InfluenceModifier;
        }

        return $sum;
    }

    private function foldAttachmentInfluence(Theah $theah, bool $announce): void
    {
        if ($this->DashedInfluence || $this->ControllerId == 0 || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        $sum = $this->attachmentInfluenceSum($theah);
        if ($sum === null)
        {
            return;
        }

        $delta = $sum - $this->TrackedAttachmentInfluence;
        if ($delta === 0)
        {
            return;
        }

        $this->UnclampedInfluence += $delta;
        $this->TrackedAttachmentInfluence = $sum;
        $oldChip = $this->ModifiedInfluence;
        $reported = max(0, $this->UnclampedInfluence);
        $this->ModifiedInfluence = $reported;
        $this->IsUpdated = true;

        if (! $announce || $reported === $oldChip)
        {
            return;
        }

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId,
            $this->Id,
            $oldChip,
            $reported,
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }
}
