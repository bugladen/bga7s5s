<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01007;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01007 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate("Aldo Bussotti");
        $this->Image = "01007.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 7;

        $this->initializeFaction("Vodacce");
        $this->Title = "'Creative' Clerk";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            "Diplomat",
            "Red Hand",
            "Vodacce",
        ];

        $this->Text = clienttranslate("<p>Aldo gains +1 [Influence] for each Renown at this location.</p><p>City Action: Move a Renown from a location you control to this one.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01007(),
        ];
    }

    private function updateInfluence(Theah $theah, int $count = 0)
    {

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            $this->Influence + $count, 
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->toLocation == Game::LOCATION_PLAYER_HOME)
        {
           $this->updateInfluence($event->theah, 0);
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->toLocation != Game::LOCATION_PLAYER_HOME)
        {
            $location = $event->theah->getCityLocation($event->toLocation);
            $this->updateInfluence($event->theah, $location->Reknown);
        }

        if ($event instanceof EventReknownAddedToLocation && $event->location == $this->Location)
        {
            $location = $event->theah->getCityLocation($event->location);
            $this->updateInfluence($event->theah, $location->Reknown);
        }

        if ($event instanceof EventReknownRemovedFromLocation && $event->location == $this->Location)
        {
            $location = $event->theah->getCityLocation($event->location);
            $this->updateInfluence($event->theah, -$location->Reknown);
        }

    }
}