<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01068 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Character you control");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $leontine = $this->getOwningCharacter($theah);
        if ( ! $leontine->hasTrait("Sorcerer"))
        {
            return false;
        }

        if ( ! $theah->cardInCity($leontine))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($leontine->Location);
        $characters = array_filter($characters, fn($character) => $character->Id != $leontine->Id && $character->ControllerId == $playerId);

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
            $game = $event->theah->game;
            $playerId = $event->playerId;

            $leontine = $this->getOwningCharacter($event->theah);

            if ( ! $event->theah->cardInCity($leontine))
            {
                throw new \BgaUserException($game->translate("Léontine is not in the City."));
            }

            $characters = $event->theah->getCharactersAtLocation($leontine->Location);
            $characters = array_filter($characters, fn($character) => $character->Id != $leontine->Id && $character->ControllerId == $playerId);
    
            if (count($characters) == 0)
            {
                throw new \BgaUserException($game->translate("There are no characters you control to move at Léontine's location."));
            }

            $revealEvent = EventFactory::createTransitionEvent($playerId, $leontine->Id, "01068", $this->Id);
            $event->queueEvent($revealEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01068)
        {
            $leontine = $this->getOwningCharacter($game->theah);
            $characters = $game->theah->getCharactersAtLocation($leontine->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Id != $leontine->Id && $character->ControllerId == $leontine->ControllerId));
    
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01068_2)
        {
            $leontine = $this->getOwningCharacter($game->theah);
            $locations = $game->theah->getCityLocations();
            $locations = array_values(array_filter($locations, fn($location) => $location->Name != $leontine->Location));

            $args["locationIds"] = array_map(fn($location) => $location->Name, $locations);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01068)
        {
            $character = $game->theah->getCharacterById($id);
            if ( ! $character)
            {
                throw new \BgaUserException($game->translate("Character not found."));
            }

            $leontine = $this->getOwningCharacter($game->theah);
            if ($character->ControllerId != $leontine->ControllerId)
            {
                throw new \BgaUserException($game->translate("You do not control that character."));
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01068_2)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($validLocation) => $validLocation->Name == $location->Name);
            if (count($locations) == 0)
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not a valid location."), $location->Name));
            }

            $leontine = $this->getOwningCharacter($game->theah);

            if ($location->Name == $leontine->Location)
            {
                throw new \BgaUserException($game->translate("Léontine cannot move character to the same location as herself."));
            }

            $woundEvent = EventFactory::createCharacterWoundedEvent($leontine->Id, $leontine->Id, 1, $game->translate("Léontine Action"));
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);

            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            $moveEvent = EventFactory::createCardMovedEvent($character->ControllerId, $character->Id, $character->Location, $location->Name, $engage = false);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the [${action}] Action from ${owner_inject_code}'), [
                "i18n" => ["action"],
                "player_name" => $game->getActivePlayerName(),
                "action" => $this->Name,
                "owner_inject_code" => $leontine->getInjectCode(),
            ]);

            $this->SetUsed($game->theah, true);

            $game->gamestate->nextState("locationChosen");
        }
    }
}