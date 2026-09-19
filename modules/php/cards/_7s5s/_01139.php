<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class _01139 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public bool $goToLocker = false;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Strength of Ten");
        $this->Image = "01139.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 139;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 1;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Relentless'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p><b>Action:</b> Spend a Renown • Take two more actions. Send this card to The Locker.</p><p><b>Maneuver:</b> Gain +X[Thrust] where X is equal to your participant's base [Combat]. Send this card to The Locker.</p>");

        $this->goToLocker = false;

        $this->resetCard();

        $this->Actions = [
            new Action_01139(),
        ];

        $this->Maneuvers = [
            new Maneuver_01139(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Action path: hand play discards with AsPlayed, then redirect to locker.
        if ($event instanceof EventCardDiscardedFromHand && $event->cardId == $this->Id && $event->AsPlayed && $this->goToLocker)
        {
            $removedEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $event->cardId);
            $event->theah->queueEvent($removedEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $this->Id);
            $event->theah->queueEvent($lockerEvent);

            $this->goToLocker = false;
            $this->IsUpdated = true;
        }

        // WHY Maneuver path uses EndOfRound (not EventDuelCalculateCombatCardStats):
        // Combat-card stats fire when the Risk is played; the Maneuver sets goToLocker
        // later on EventDuelCalculateManeuverValues. Listening on stats meant the flag
        // was always still false, so the card sat on the Dueling Line and was discarded
        // at duel end. Defer like Technique_02043a so the card stays in Theah::$cards
        // for the rest of the round, then leave the line before stDuelEnd's discard sweep.
        if ($event instanceof EventDuelEndOfRound && $this->goToLocker)
        {
            $this->sendToLocker($event);
        }

        if ($event instanceof EventDuelEnd && $this->goToLocker)
        {
            $this->sendToLocker($event);
        }
    }

    private function sendToLocker(Event $event): void
    {
        $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $this->Id);
        $event->theah->queueEvent($lockerEvent);

        $this->goToLocker = false;
        $this->IsUpdated = true;
    }

}