<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;

class _03049 extends Leader implements IHasReactions, IHasTechniques
{
    use ReactionTrait;
    use TechniqueTrait;

    // Plunder Collect path (Take → Gains → Removed)
    private int $pendingFewerGainPlayerId = 0;
    private string $pendingFewerRemoveLocation = '';

    // Direct Collect path (Removed → Gains). Armed on opponent Remove; confirmed on Gains.
    private int $pendingCollectArmPlayerId = 0;
    private string $pendingCollectArmLocation = '';
    private string $pendingPutBackLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ekaterina Ilyanava");
        $this->Title = clienttranslate("Favored Daughter");
        $this->Image = '03049.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 49;

        $this->initializeFaction("Ussura");

        $this->Resolve = 6;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 3;
        $this->CrewCap = 5;
        $this->Panache = 6;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Hero"),
            clienttranslate("Academic"),
            clienttranslate("Explorer"),
            clienttranslate("Ussura")
        ];

        $this->Text = clienttranslate("<p>After Ekaterina's location is claimed, she may move to a different <b>City</b> location.</p>
        <p>When an opponent collects Renown from this location, they collect one fewer. <i>(Remaining Renown stays).</i></p>
        <p><b>Technique:</b> +1[Parry]. You may engage an <b>Artifact</b> equipped to Ekaterina for +2[Parry] instead.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03049(),
        ];

        $this->Techniques = [
            new Technique_03049(),
        ];
    }

    private function isPassiveLive(Event $event): bool
    {
        if ($this->ControllerId == 0)
        {
            return false;
        }
        if ($event->theah->game->characterIsInDiscardOrLocker($this))
        {
            return false;
        }
        if (! $event->theah->cardInCity($this))
        {
            return false;
        }
        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if (! $this->isPassiveLive($event))
        {
            return;
        }

        // WHY: Collect arms on Opponent Removed and confirms on the *immediately next*
        // matching Gains. Any intervening eventCheck means it wasn't Collect (Move,
        // discard-only Remove, etc.) — drop the arm so a later unrelated Gains can't
        // falsely put Renown back.
        if ($this->pendingCollectArmPlayerId != 0)
        {
            $confirmingGain = $event instanceof EventPlayerGainsReknown
                && $event->playerId == $this->pendingCollectArmPlayerId;
            if (! $confirmingGain)
            {
                $this->pendingCollectArmPlayerId = 0;
                $this->pendingCollectArmLocation = '';
                $this->IsUpdated = true;
            }
        }

        // Plunder: stPlunderGainRenown queues Take → Gains → Removed with amounts from location Renown.
        // Mutating all three here (via pending flags) is the only place with a location+player signal.
        if ($event instanceof EventPlayerTakeReknownForControlledLocation)
        {
            if ($event->location == $this->Location
                && $event->playerId != $this->ControllerId
                && $event->reknown > 0)
            {
                $originalAmount = $event->reknown;
                $event->reknown--;
                $this->pendingFewerGainPlayerId = $event->playerId;
                $this->pendingFewerRemoveLocation = $event->location;
                $this->IsUpdated = true;

                $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: Opponent collects one fewer Renown from ${location_name} (which had ${original_amount} on it). Remaining Renown stays.'), [
                    "i18n" => ["location_name"],
                    "card_inject_code" => $this->getInjectCode(),
                    "location_name" => $event->location,
                    "original_amount" => $originalAmount,
                ]);
            }
            return;
        }

        if ($event instanceof EventPlayerGainsReknown)
        {
            // Plunder Gains (armed by Take)
            if ($this->pendingFewerGainPlayerId != 0
                && $event->playerId == $this->pendingFewerGainPlayerId
                && $event->amount > 0)
            {
                $event->amount--;
                $this->pendingFewerGainPlayerId = 0;
                $this->IsUpdated = true;
                return;
            }

            // Direct Collect Gains (armed by opponent Removed from this location)
            if ($this->pendingCollectArmPlayerId != 0
                && $event->playerId == $this->pendingCollectArmPlayerId
                && $event->amount > 0)
            {
                $originalAmount = $event->amount;
                $event->amount--;
                // WHY: Collect queues Removed before Gains; at arm time we can't tell Collect
                // from a Renown Move. Confirming on Gains lets us leave Remaining via put-back.
                $this->pendingPutBackLocation = $this->pendingCollectArmLocation;
                $this->pendingCollectArmPlayerId = 0;
                $this->pendingCollectArmLocation = '';
                $this->IsUpdated = true;

                $event->theah->game->notify->all("message", clienttranslate('${card_inject_code}: Opponent collects one fewer Renown from ${location_name} (which had ${original_amount} on it). Remaining Renown stays.'), [
                    "i18n" => ["location_name"],
                    "card_inject_code" => $this->getInjectCode(),
                    "location_name" => $this->pendingPutBackLocation,
                    "original_amount" => $originalAmount,
                ]);
            }
            return;
        }

        if ($event instanceof EventRenownRemovedFromLocation)
        {
            // Plunder Removed (armed by Take). playerId is 0 on plunder's createEvent path.
            if ($this->pendingFewerRemoveLocation != ''
                && $event->location == $this->pendingFewerRemoveLocation
                && $event->amount > 0)
            {
                $event->amount--;
                $this->pendingFewerRemoveLocation = '';
                $this->IsUpdated = true;
                return;
            }

            // Speculatively arm for Collect (Removed → Gains). Do NOT arm Moves: those are
            // followed by RenownAdded (isMove), which clears the arm without reducing.
            if ($event->location == $this->Location
                && $event->playerId != 0
                && $event->playerId != $this->ControllerId
                && $event->amount > 0)
            {
                $this->pendingCollectArmPlayerId = $event->playerId;
                $this->pendingCollectArmLocation = $event->location;
                $this->IsUpdated = true;
            }
            return;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY handleEvent (not eventCheck): put-back Add must run AFTER the already-queued
        // full Removed applies, so net location Renown loses one fewer than Collect intended.
        if ($event instanceof EventPlayerGainsReknown && $this->pendingPutBackLocation != '')
        {
            $location = $this->pendingPutBackLocation;
            $this->pendingPutBackLocation = '';
            $this->IsUpdated = true;

            $addEvent = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                $location,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($addEvent);
        }
    }
}
