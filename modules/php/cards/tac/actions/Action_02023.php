<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02023 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Move target opposing non-Leader to an adjacent City location with less Renown');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (!$theah->cardInCity($owner))
        {
            return false;
        }

        $characters = $theah->getOpposingCharactersAtLocation($owner->Location, $playerId); 
        $characters = array_filter($characters, fn($c) => ! $c->hasTrait("Leader"));
        if (count($characters) == 0)
        {
            return false;
        }

        $location = $theah->getCityLocation($owner->Location);
        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacentLocations as $adjacentLocationName)
        {
            $adjacentLocation = $theah->getCityLocation($adjacentLocationName);
            if ($adjacentLocation->Renown < $location->Renown)
            {
                return true;
            }
        } 

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02023", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02023)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $characters = array_filter($characters, fn($c) => ! $c->hasTrait("Leader"));
            $args['ids'] = array_map(fn($c) => $c->Id, array_values($characters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02023_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['characterId'] = $characterId;

            $performerLocation = $game->theah->getCityLocation($performer->Location);
            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            $availableLocations = [];
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $adjacentLocation = $game->theah->getCityLocation($adjacentLocation);
                if ($adjacentLocation->Renown < $performerLocation->Renown)
                {
                    $availableLocations[] = $adjacentLocation->Name;
                }
            }
            $args['locationIds'] = $availableLocations;

        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02023)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new UserException($game->translate("You cannot choose your own character"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new UserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($character->hasTrait("Leader"))
            {
                throw new UserException($game->translate("You cannot choose a Leader"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $id);
            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02023_2)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            if (! in_array($location, $adjacentLocations))
            {
                throw new UserException($game->translate("Location is not adjacent to the performer"));
            }

            $performerLocation = $game->theah->getCityLocation($performer->Location);
            $selectedLocation = $game->theah->getCityLocation($location);

            if ($selectedLocation->Renown >= $performerLocation->Renown)
            {
                throw new UserException($game->translate("Location has the same or greater Renown as the performer"));
            }

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);

            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, $location, $engage = false, $performer->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->resetPlayerPassCount($game);
            $this->announceAction($game);
            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("locationChosen");
        }
    }
}