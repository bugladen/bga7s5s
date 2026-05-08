<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_02040 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Performer Home and Heal");
        $this->RequiresPerformerSelected = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($event->theah);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $performer->Id, $performer->Location, Game::LOCATION_PLAYER_HOME, true, $owner->Id, $this->Id);
            $event->theah->queueEvent($moveEvent);

            if ($performer->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($healEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
