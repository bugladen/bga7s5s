<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01189a extends EventCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Point of Opportunity: Move Reknown From Adjacent Location";
        $this->ShortName = "Move Reknown From Adjacent Location";

        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $poo = $this->getOwningCard($theah);
        $locations = $theah->getAdjacentCityLocations($poo->Location);
        $locations = array_filter($locations, fn($location) => $theah->game->getReknownForLocation($location) > 0);

        if (count($locations) == 0)
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01189a");
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $performerId = $game->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        $currentLocation = $performer->Location;

        $locations = $game->theah->getAdjacentCityLocations($currentLocation, $includeHome = false);

        //Filter out locations that do not have at least one Reknown
        $locations = array_values(array_filter($locations, fn($location) => $game->getReknownForLocation($location) > 0));

        $args["locations"] = $locations;
        $args["performerId"] = $performerId;

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        $location = $game->theah->getCityLocation($ids[0]);

        $performerId = $game->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        $poo = $this->getOwningCard($game->theah);
        $currentLocation = $poo->Location;

        $locations = $game->theah->getAdjacentCityLocations($currentLocation, $includeHome = false);
        if ( ! in_array($location->Name, $locations))
        {
            throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to Location %s."), $location->Name, $currentLocation));
        }

        //Check if the origin location has reknown to move
        $reknown = $game->getReknownForLocation($location->Name);
        if ($reknown <= 0)
        {
            throw new \BgaUserException(sprintf($game->translate("%s does not have any reknown to move."), $location->Name));
        }

        $fromEvent = EventFactory::createReknownRemovedFromLocationEvent($performer->ControllerId, $location->Name, 1, "Point of Opportunity: Moving Reknown from adjacent location");
        $game->theah->eventCheck($fromEvent);

        $toEvent = EventFactory::createReknownAddedToLocationEvent($performer->ControllerId, $poo->Location, 1, "Point of Opportunity: Moving Reknown from adjacent location to an adjacent location");
        $game->theah->eventCheck($toEvent);

        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($poo->ControllerId, $poo->Id, $poo->Location);
        $game->theah->eventCheck($discardEvent);

        $game->theah->queueEvent($fromEvent);
        $game->theah->queueEvent($toEvent);
        $game->theah->queueEvent($discardEvent);

        $game->notifyAllPlayers("message", clienttranslate('${player_name} has activated Point of Opportunity'), [
            'player_name' => $game->getActivePlayerName(),
        ]);

        $game->gamestate->nextState("locationChosen");
    }
}
