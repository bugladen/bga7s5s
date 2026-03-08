<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01138 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate Target Character in Adjacent Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($characters as $character)
        {
            $adjacentLocations = $theah->getAdjacentCityLocations($character->Location, $includeHome = false);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
                if (count($opposingCharacters) > 0)
                {
                    $availablePerformers[$character->Id] = $character;
                }
            }
        }

        return count($availablePerformers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($characters as $character)
        {
            $adjacentLocations = $theah->getAdjacentCityLocations($character->Location, $includeHome = false);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
                if (count($opposingCharacters) > 0)
                {
                    $availablePerformers[$character->Id] = $character;
                }
            }
        }

        return array_values($availablePerformers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01138", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01138)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            $availableOpposingCharacters = [];
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($performer->ControllerId));
                if (count($opposingCharacters) > 0)
                {
                    $availableOpposingCharacters = array_merge($availableOpposingCharacters, $opposingCharacters);
                }
            }

            $args["ids"] = array_map(fn($character) => $character->Id, array_values($availableOpposingCharacters));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot manipulate your own character")];
        }

        $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
        if (! in_array($character->Location, $adjacentLocations))
        {
            return [false, $game->translate("Character is not in an adjacent location")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01138)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            if ($performer->Engaged)
            {
                $owner = $this->getOwningCard($game->theah);

                //Move Performer to Target's Location
                $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $target->Location, $engage = false, $owner->Id, $this->Id);
                $game->theah->queueEvent($moveEvent);

                //Wound Target
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $this->setUsed($game->theah, true);
                $this->resetPlayerPassCount($game);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("wound");
            }
            else
            {
                $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
                $game->gamestate->nextState("manipulate");
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01138_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $owner = $this->getOwningCard($game->theah);

            //Move Performer to Target's Location
            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $target->Location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            //Choose to Engage
            if ($id == 1)
            {
                //Engage Performer
                $event = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $performer->Id, $this->Id);
                $game->theah->queueEvent($event);

                //Move Target HOME
                $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $target->Id, $target->Location, Game::LOCATION_PLAYER_HOME, $engage = false, $owner->Id, $this->Id);
                $game->theah->queueEvent($moveEvent);
            }
            //Choose to Do Not Engage
            else if ($id == 0)
            {
                //Wound Target
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

            }

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}