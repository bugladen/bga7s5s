<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01117 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown to Location. Move Ekaterina to different Location.");        
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $ekaterina = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($ekaterina))
        {
            return false;
        }

        $location = $theah->getCityLocation($ekaterina->Location);
        return $location->Reknown > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01117", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01117)
        {
            $ekaterina = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $ekaterina->Id;

            $locations = $game->theah->getCityLocations();
            $locations = array_values(array_filter($locations, fn($location) => $location->Name != $ekaterina->Location));
            $args["locationIds"] = array_map(fn($location) => $location->Name, $locations);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01117_2)
        {
            $ekaterina = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $ekaterina->Id;

            $chosenLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            $args["chosenLocation"] = $chosenLocation;

            $locations = $game->theah->getCityLocations();
            $locations = array_values(array_filter($locations, fn($location) => $location->Name != $ekaterina->Location && $location->Name != $chosenLocation));
            $args["locationIds"] = array_map(fn($location) => $location->Name, $locations);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01117)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            if (! $game->theah->locationInCity($location->Name))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not in the city."), $game->translate($location->Name)));
            }

            $ekaterina = $this->getOwningCharacter($game->theah);

            if ($ekaterina->Location == $location->Name)
            {
                throw new \BgaUserException(sprintf($game->translate("Ekaterina Ilyanava is at Location %s."), $game->translate($location->Name)));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $location->Name);

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01117_2)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            if (! $game->theah->locationInCity($location->Name))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not in the city."), $game->translate($location->Name)));
            }

            $ekaterina = $this->getOwningCharacter($game->theah);

            if ($ekaterina->Location == $location->Name)
            {
                throw new \BgaUserException(sprintf($game->translate("Ekaterina Ilyanava is at Location %s."), $game->translate($location->Name)));
            }

            $reknownMoveLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            if ($reknownMoveLocation == $location->Name)
            {
                throw new \BgaUserException(sprintf($game->translate("That location has been chosen already to move Renown to. Choose a different location."), $game->translate($location->Name)));
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $reknownRemovedEvent = EventFactory::createReknownRemovedFromLocationEvent($ekaterina->ControllerId, $ekaterina->Location, 1, $ekaterina->getInjectCode());
            $game->theah->queueEvent($reknownRemovedEvent);

            $reknownAddedEvent = EventFactory::createReknownAddedToLocationEvent($ekaterina->ControllerId, $reknownMoveLocation, 1, $ekaterina->getInjectCode(), $isMove = true);
            $game->theah->eventCheck($reknownAddedEvent);
            $game->theah->queueEvent($reknownAddedEvent);

            $ekaterinaMovedEvent = EventFactory::createCardMovedEvent($ekaterina->ControllerId, $ekaterina->Id, $ekaterina->Location, $location->Name, $engage = false, $ekaterina->Id, $this->Id);
            $game->theah->eventCheck($ekaterinaMovedEvent);
            $game->theah->queueEvent($ekaterinaMovedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($ekaterina->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
    
        }
    }
}