<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01189a extends EventCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Renown from Adjacent Location");

        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $poo = $this->getOwningCard($theah);
        $locations = $theah->getAdjacentCityLocations($poo->Location, $includeHome = false);
        $locations = array_filter($locations, fn($location) => $theah->game->getRenownForLocation($location) > 0);

        if (count($locations) == 0)
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($poo->Location, $playerId);
        $characters = array_filter($characters, fn($character) => ! $character->Engaged);
        if (count($characters) == 0)
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01189a", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_filter($performers, fn($performer) => ! $performer->Engaged);
        return array_values($performers);
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01189a)
        {
            $performerId = $game->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $currentLocation = $performer->Location;
    
            $locations = $game->theah->getAdjacentCityLocations($currentLocation, $includeHome = false);
    
            //Filter out locations that do not have at least one Reknown
            $locations = array_values(array_filter($locations, fn($location) => $game->getRenownForLocation($location) > 0));
    
            $args["locations"] = $locations;
            $args["performerId"] = $performerId;
    
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01189a)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $performerId = $game->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $poo = $this->getOwningCard($game->theah);
            $currentLocation = $poo->Location;
    
            $locations = $game->theah->getAdjacentCityLocations($currentLocation, $includeHome = false);
            if ( ! in_array($location->Name, $locations))
            {
                throw new UserException(sprintf($game->translate("Location %s is not adjacent to Location %s."), $location->Name, $currentLocation));
            }
    
            //Check if the origin location has reknown to move
            $reknown = $game->getRenownForLocation($location->Name);
            if ($reknown <= 0)
            {
                throw new UserException(sprintf($game->translate("%s does not have any Renown to move."), $location->Name));
            }
    
            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $poo->Id, $this->Id);
            $game->theah->eventCheck($engageEvent);

            $fromEvent = EventFactory::createRenownRemovedFromLocationEvent($performer->ControllerId, $location->Name, 1, "{$poo->getInjectCode()}: Moving Renown from adjacent location");
            $game->theah->eventCheck($fromEvent);
    
            $toEvent = EventFactory::createReknownAddedToLocationEvent($performer->ControllerId, $poo->Location, 1, "{$poo->getInjectCode()}: Moving Renown from adjacent location to an adjacent location", $isMove = true);
            $game->theah->eventCheck($toEvent);
    
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($poo->ControllerId, $poo->Id, $poo->Location, $poo->Id, $asEffect = true);
            $game->theah->eventCheck($discardEvent);

            $game->theah->queueEvent($engageEvent);
            $game->theah->queueEvent($fromEvent);
            $game->theah->queueEvent($toEvent);
            $game->theah->queueEvent($discardEvent);
    
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
