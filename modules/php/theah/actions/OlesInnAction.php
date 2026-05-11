<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class OlesInnAction extends LocationAction
{
    public function __construct(array $playersUsed, Game $game)
    {
        parent::__construct($playersUsed, $game);

        $this->Id = 'OlesInn';
        $this->Name = "Ole's Inn: Draw a card";
        $this->LocationName = Game::LOCATION_CITY_OLES_INN;
        $this->globalVariableName = Game::PLAYERS_THAT_USED_OLES_INN;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if ($theah->game->getPlayerCount() < 3)
        {
            return false;
        }

        $location = $theah->getCityLocation(Game::LOCATION_CITY_OLES_INN);
        return $location->Controller == $playerId;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $drawEvent = EventFactory::createCardDrawnEvent($event->playerId, $event->theah->game->translate("Ole's Inn: Draw a card"));
            $event->theah->queueEvent($drawEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}