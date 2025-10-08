<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01093 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move to Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01093", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01093)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $isFirstPlayer = $game->globals->get(Game::FIRST_PLAYER) == $performer->ControllerId;
            if ($isFirstPlayer)
            {
                $args["locationIds"] = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = true);
            }
            else
            {
                $locations = $game->theah->getCityLocations();
                $locations = array_values(array_filter($locations, fn($location) => $location->Name != $performer->Location));
                $locations = array_map(fn($location) => $location->Name, $locations);
                $locations[] = Game::LOCATION_PLAYER_HOME;
                $args["locationIds"] = $locations;
            }
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01093)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $owner = $this->getOwningCharacter($game->theah);
            $isFirstPlayer = $game->globals->get(Game::FIRST_PLAYER) == $owner->ControllerId;

            if ($isFirstPlayer)
            {
                $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = true);
                if ( ! in_array($location->Name, $locations))
                {
                    throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to %s."), $location->Name, $owner->Location));
                }

            }

            if ($location->Name == $owner->Location)
            {
                throw new \BgaUserException($game->translate("You cannot move to the same location as Maya."));
            }

            $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $owner->Id, $owner->Location, $location->Name, $engage = false, $owner->Id);
            $game->theah->queueEvent($moveEvent);

            $this->resetPlayerPassCount($game);
            $this->announceAction($game);
            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
