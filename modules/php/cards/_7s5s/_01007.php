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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
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
        $this->Title = clienttranslate("'Creative' Clerk");
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Diplomat"),
            clienttranslate("Red Hand"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p>Aldo gains +1 [Influence] for each Renown at this location.</p><p><b>City Action:</b> Move a Renown from a location you control to this one.</p>");

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

    // WHY: Fate's Silence skips handleEvent — Renown-driven Influence would stick.
    public function onAbilitiesBlanked(Theah $theah): void
    {
        if ($this->ControllerId == 0)
        {
            return;
        }
        $this->updateInfluence($theah, 0);
    }

    public function onAbilitiesUnblanked(Theah $theah): void
    {
        if ($this->IsDying || $this->ControllerId == 0 || $theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }
        if ($this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $this->updateInfluence($theah, 0);
            return;
        }

        $location = $theah->getCityLocation($this->Location);
        $this->updateInfluence($theah, $location->Renown);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->abilitiesAreBlanked())
        {
            return;
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->toLocation == Game::LOCATION_PLAYER_HOME)
        {
           $this->updateInfluence($event->theah, 0);
        }

        if ($event instanceof EventCardMoved && $event->cardId == $this->Id && $event->toLocation != Game::LOCATION_PLAYER_HOME)
        {
            $location = $event->theah->getCityLocation($event->toLocation);
            $this->updateInfluence($event->theah, $location->Renown);
        }

        if ($event instanceof EventRenownAddedToLocation && $event->location == $this->Location)
        {
            $location = $event->theah->getCityLocation($event->location);
            $this->updateInfluence($event->theah, $location->Renown);
        }

        if ($event instanceof EventRenownRemovedFromLocation && $event->location == $this->Location)
        {
            $location = $event->theah->getCityLocation($event->location);
            $this->updateInfluence($event->theah, $location->Renown);
        }

    }
}