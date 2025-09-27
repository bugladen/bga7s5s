<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class GovernorsGardenAction extends LocationAction
{
    public function __construct(array $playersUsed, Game $game)
    {
        parent::__construct($playersUsed, $game);

        $this->Id = 'GovernorsGarden';
        $this->Name = "Governor's Garden: Draw a card";
        $this->globalVariableName = Game::PLAYERS_THAT_USED_GOVERNORS_GARDEN;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if ($theah->game->getPlayerCount() < 4)
        {
            return false;
        }

        $location = $theah->getCityLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        return $location->Controller == $playerId;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->game->notify->all('message', clienttranslate('${player_name} performed Governor\'s Garden action to draw a card'), [
                'player_name' => $event->theah->game->getPlayerNameById($event->playerId)
            ]);

            $drawEvent = EventFactory::createCardDrawnEvent($event->playerId, $event->theah->game->translate("Governor's Garden: Draw a card"));
            $event->theah->queueEvent($drawEvent);

            $this->setPlayerUsed($event->playerId);
            $this->resetPlayerPassCount($this->game);
        }
    }}
