<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02036b extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate('Move adjacent performer to City Docks');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $pirates = $theah->getCharactersInCityByPlayerId($playerId);
        $pirates = array_filter($pirates, fn ($c) => $c->hasTrait("Pirate"));
        foreach ($pirates as $pirate) {
            if ($pirate->Location == Game::LOCATION_CITY_DOCKS) {
                continue;
            }
            $adj = $theah->getAdjacentCityLocations($pirate->Location, false);
            if (in_array(Game::LOCATION_CITY_DOCKS, $adj, true)) {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_filter($performers, fn ($c) => $c->hasTrait("Pirate"));
        $performers = array_filter($performers, function ($c) use ($theah) {
            if ($c->Location == Game::LOCATION_CITY_DOCKS) {
                return false;
            }
            $adj = $theah->getAdjacentCityLocations($c->Location, false);

            return in_array(Game::LOCATION_CITY_DOCKS, $adj, true);
        });

        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            if ($performer === null) {
                return;
            }

            $this->announceAction($game);

            $move = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $performer->Id,
                $performer->Location,
                Game::LOCATION_CITY_DOCKS,
                false,
                $owner->Id,
                $this->Id
            );
            $event->theah->eventCheck($move);
            $event->theah->queueEvent($move);

            $this->resetPlayerPassCount($game);
            $this->setUsed($event->theah, true);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
