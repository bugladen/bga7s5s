<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01111;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _01111 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Research");
        $this->Image = "img/cards/7s5s/111.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Discovery',
            'Scholarship',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01111(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // If played this card goes to the locker
        if ($event instanceof EventCardDiscardedFromHand && $event->cardId == $this->Id && $event->AsPlayed)
        {
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->playerId, $event->cardId);
            $event->theah->queueEvent($removeEvent);
            
            $lockerEvent = EventFactory::createCardSentToLockerEvent($event->playerId, $event->cardId);
            $event->theah->queueEvent($lockerEvent);
        }
    }
}