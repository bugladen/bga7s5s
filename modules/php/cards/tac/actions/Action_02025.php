<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02025 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate('Diplomat City Action: Move opposing character, performer, and Renown');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $diplomats = $theah->getCharactersInCityByPlayerId($playerId);
        $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat"));

        foreach ($diplomats as $diplomat)
        {
            $opponents = $theah->getOpposingCharactersAtLocation($diplomat->Location, $playerId);
            $opponents = array_filter($opponents, fn($c) => $c->Influence <= $diplomat->Influence);
            if (count($opponents) > 0)
            {
                $adjacentLocations = $theah->getAdjacentCityLocations($diplomat->Location, $includeHome = false);
                if (count($adjacentLocations) > 0)
                {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_filter($performers, fn($c) => $c->hasTrait("Diplomat"));
        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02025", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $characters = array_filter($characters, fn($c) => $c->Influence <= $performer->Influence);
            $args['ids'] = array_map(fn($c) => $c->Id, array_values($characters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['characterId'] = $characterId;

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            $args['locationIds'] = $adjacentLocations;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025)
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

            if ($character->Influence > $performer->Influence)
            {
                throw new UserException($game->translate("Target character has higher Influence than the performer"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $id);
            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02025_2)
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

            $characterId = $game->globals->get(Game::CHOSEN_TARGET);
            $character = $game->theah->getCharacterById($characterId);
            $sourceLocation = $performer->Location;
            $owner = $this->getOwningCard($game->theah);

            $moveTarget = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveTarget);

            $movePerformer = EventFactory::createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($movePerformer);

            $sourceLocationObj = $game->theah->getCityLocation($sourceLocation);
            if ($sourceLocationObj->Renown > 0)
            {
                $removeRenown = EventFactory::createReknownRemovedFromLocationEvent($performer->ControllerId, $sourceLocation, 1, $owner->getInjectCode());
                $game->theah->queueEvent($removeRenown);

                $addRenown = EventFactory::createReknownAddedToLocationEvent($performer->ControllerId, $location, 1, $owner->getInjectCode(), $isMove = true);
                $game->theah->queueEvent($addRenown);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
