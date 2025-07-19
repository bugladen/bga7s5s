<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01076 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Performer to City Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $sorcerers = array_filter($characters, fn($character) => $character->hasTrait("Sorcerer"));

        if (count($sorcerers) == 0)
        {
            return false;
        }

        return true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $performers = array_values(array_filter($performers, fn($character) => $character->hasTrait("Sorcerer")));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01076", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        // Choose location
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01076)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);

            $locations = array_values($game->theah->getCityLocations());
            $locations = array_filter($locations, fn($location) => $location->Name != $performer->Location);
            $locationIds = array_map(fn($location) => $location->Name, $locations);
            $args["locationIds"] = $locationIds;

            $args["performerId"] = $game->globals->get(Game::CHOSEN_PERFORMER);
        }

        // Choose character to bring along
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01076_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);

            $characters = $game->theah->getCharactersInPlayByPlayerId($performer->ControllerId);
            $characters = array_values(array_filter($characters, fn($character) => $character->Location == $performer->Location && $character->Id != $performerId));
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);

            $args["performerId"] = $game->globals->get(Game::CHOSEN_PERFORMER);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        //Character chosen to bring along
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01076_2)
        {
            $bloodMark = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);

            if ($id > 0)
            {
                $character = $game->theah->getCardById($id);
                if ($character == null)
                {
                    throw new \BgaUserException($game->translate("Character not found"));
                }

                $event = EventFactory::createCharacterWoundedEvent($performer->Id, $bloodMark->Id, 1, $game->translate("Blood Mark"));
                $game->theah->queueEvent($event);
            }

            $locationName = $game->globals->get(Game::CHOSEN_LOCATION);

            //Move Performer to chosen location
            $event = EventFactory::createCardMovedEvent($performer->ControllerId, $performer->Id, $performer->Location, $locationName, false, $bloodMark->Id);
            $game->theah->queueEvent($event);

            if ($id > 0)
            {
                $event = EventFactory::createCardMovedEvent($character->ControllerId, $character->Id, $character->Location, $locationName, false, $bloodMark->Id);
                $game->theah->queueEvent($event);
            }

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        //Location chosen
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01076)
        {
            $location = $game->theah->getCityLocation($ids[0]);
            $game->globals->set(Game::CHOSEN_LOCATION, $location->Name);

            $game->gamestate->nextState("locationChosen");
        }
    }
}