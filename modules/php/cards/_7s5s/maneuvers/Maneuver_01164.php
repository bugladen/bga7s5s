<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01164 extends Maneuver
{
    private int $MoveCharacter = 0;
    private string $MoveLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move to Adjacent Location");
        $this->MoveCharacter = 0;
        $this->MoveLocation = "";
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

        if ($event instanceof EventDuelEndOfRound && $this->MoveCharacter != 0)
        {
            $game = $event->theah->game;
            $character = $game->theah->getCharacterById($this->MoveCharacter);

            if (! $game->characterIsInDiscardOrLocker($character))
            {
                $owner = $this->getOwningCard($event->theah);
                $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $this->MoveLocation, $engage = false, $owner->Id, $this->Id);
                $event->theah->queueEvent($moveEvent);
            }

            $this->MoveCharacter = 0;
            $this->MoveLocation = "";
            $character->IsUpdated = true;
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
            $this->MoveCharacter = $actor->Id;
            $this->MoveLocation = $location;
            $game->updateCardObjectInDb($owner);            

            $game->gamestate->nextState();
        }
    }
}