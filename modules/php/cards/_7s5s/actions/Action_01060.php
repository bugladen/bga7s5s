<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_01060 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Characters to an Adjacent Location");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01060", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060)
        {
            $owner = $this->getOwningCard($game->theah);
            $availableLocations = [];

            $performers = $game->theah->getCharactersAtLocation(Game::LOCATION_PLAYER_HOME);
            if (count($performers) > 0)
            {
                $availableLocations[] = Game::LOCATION_PLAYER_HOME;
            }

            $locations = $game->theah->getCityLocations();
            foreach ($locations as $location)
            {
                $performers = $game->theah->getCharactersAtLocation($location->Name);
                $performers = array_filter($performers, fn($performer) => $performer->ControllerId == $owner->ControllerId);
                if (count($performers) > 0)
                {
                    $availableLocations[] = $location->Name;
                }
            }
            $args["locationIds"] = $availableLocations;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $performers = $game->theah->getCharactersAtLocation($location);
            $performers = array_values(array_filter($performers, fn($performer) => $performer->ControllerId == $owner->ControllerId));

            $args["characterIds"] = array_map(fn($character) => $character->Id, $performers);        
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060_3)
        {
            $owner = $this->getOwningCard($game->theah);
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $availableLocations = $game->theah->getAdjacentCityLocations($location);
            $args["locationIds"] = $availableLocations;

            $performerJson = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performerIds = json_decode($performerJson, true);            
            $args["characterIds"] = $performerIds;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060)
        {
            $location = $ids[0];
            $performers = $game->theah->getCharactersAtLocation($location);
            if (count($performers) == 0)
            {
                throw new \BgaUserException($game->translate("You have no Performers at this location"));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $location);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060_2)
        {
            foreach ($ids as $id)
            {
                $owner = $this->getOwningCard($game->theah);
                $location = $game->globals->get(Game::CHOSEN_LOCATION);
                $character = $game->theah->getCharacterById($id);

                if ($character == null)
                {   
                    throw new \BgaUserException($game->translate("Character not found"));
                }

                if ($character->ControllerId != $owner->ControllerId)
                {
                    throw new \BgaUserException($game->translate("You do not own this character"));
                }

                if ($character->Location != $location)
                {
                    throw new \BgaUserException($game->translate("Character is not at the chosen location"));
                }
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, json_encode($ids));
            $game->gamestate->nextState("performersChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01060_3)
        {
            $location = $ids[0];
            $fromLocation = $game->globals->get(Game::CHOSEN_LOCATION);

            $availableLocations = $game->theah->getAdjacentCityLocations($fromLocation);
            if (!in_array($location, $availableLocations))
            {
                throw new \BgaUserException(sprintf($game->translate("%s is not adjacent to %s"), $location, $fromLocation));
            }

            $performerJson = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performerIds = json_decode($performerJson, true);

            $owner = $this->getOwningCard($game->theah);
            foreach ($performerIds as $performerId)
            {
                $performer = $game->theah->getCharacterById($performerId);
                $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $performer->Id, $performer->Location, $location, $engage = false, $owner->Id);
                $game->theah->queueEvent($moveEvent);
            }

            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("locationChosen");
        }
    }

}