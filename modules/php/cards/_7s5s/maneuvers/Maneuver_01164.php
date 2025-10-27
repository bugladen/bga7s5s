<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01164 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move to Adjacent Location");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01164", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01164)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args["locationIds"] = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
        }

        return $args;
    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01164)
        {
            $location = $ids[0];
            $actor = $game->theah->getDuelRoundActor();
    
            $locations = $game->theah->getAdjacentCityLocations($actor->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate('Location is not adjacent to %s.'), $actor->Name));
            }

            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $actor->Id, $actor->Location, $location, $engage = false, $owner->Id);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState();
        }
    }
}