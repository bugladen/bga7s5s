<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01123 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Challenge Enemy Character at Adjacent Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner->Engaged)
        {
            return false;
        }

        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacentLocations as $adjacentLocation)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
            $opposingCharacter = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
            if (count($opposingCharacter) > 0)
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
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01123", $this->Id);
            $event->theah->queueEvent($transition);
        }
    } 

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01123)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;
            
            $adjacentLocations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($owner->ControllerId));
                foreach ($opposingCharacters as $opposingCharacter)
                {
                    $charactersIds[] = $opposingCharacter->Id;
                }
            }

            $args['ids'] = $charactersIds;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($character->ControllerId == $owner->ControllerId)
        {
            return [false, $game->translate("You cannot challenge your own character")];
        }

        $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (! in_array($character->Location, $locations))
        {
            return [false, $game->translate("Target character is not at an adjacent location")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01123)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $owner->Id, $owner->Location, $target->Location, true, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::VALERI_MIKHAILOV_CHALLENGE_TYPE);

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01123_2", $this->Id);
            $game->theah->queueEvent($transition);

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            // createActionResolvedEvent() not needed because this action starts a challenge

            $game->gamestate->nextState("opponentChosen");
        }
    }
}