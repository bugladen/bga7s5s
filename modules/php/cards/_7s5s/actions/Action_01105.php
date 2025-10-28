<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01105 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Pressure Location with Resolve. Engage Character.");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        foreach ($characters as $character)
        {
            $opposingCharacters = $theah->getOpposingCharactersAtLocation($character->Location, $playerId);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => ! $character->Engaged);
            if (count($opposingCharacters) > 0)
            {
                return true;
            }
        }

        return false;        
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = [];
        foreach ($characters as $character)
        {
            $opposingCharacters = $theah->getOpposingCharactersAtLocation($character->Location, $playerId);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => ! $character->Engaged);
            if (count($opposingCharacters) > 0)
            {
                $performers[] = $character;
            }
        }

        return $performers;
    }

    public function getOpposingCharacters(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = [];
        foreach ($characters as $character)
        {
            $opposingCharacters = $theah->getOpposingCharactersAtLocation($character->Location, $playerId);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => ! $character->Engaged);
            if (count($opposingCharacters) > 0)
            {
                $performers[] = $character;
            }
        }
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->globals->set(Game::PRESSURING_PLAYER, $performer->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_RESOLVE);

            $pressureStats = $event->theah->getPressureStats($performer, Game::STAT_RESOLVE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($performer->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureOccuringEvent);

            //Go straight to stHighDramaPressureLocation
            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);

        }
    
        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            if ($event->success)
            {
                $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01105", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01105)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01105)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot engage your own character."));
            }

            if ($character->Engaged)
            {
                throw new \BgaUserException($game->translate("Character is already engaged."));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer."));
            }

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $character->Id);
            $game->theah->queueEvent($engageEvent);

            $game->gamestate->nextState();
        }
    }
}
