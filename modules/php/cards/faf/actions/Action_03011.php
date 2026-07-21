<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03011 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Adjacent Thug or Bodyguard to Performer's Location");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("You may only move one of your own characters.")];
        }

        if (! ($character->hasTrait("Thug") || $character->hasTrait("Bodyguard")))
        {
            return [false, $game->translate("Character must be a Thug or Bodyguard.")];
        }

        $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = true);
        if (! in_array($character->Location, $adjacentLocations))
        {
            return [false, $game->translate("Character must be at a location adjacent to your performer.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03011", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03011)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer->Id;
            $args['ids'] = array_map(fn(Character $c) => $c->Id, $this->getValidTargets($game->theah, $performer));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03011)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($game->theah);

            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, $performer->Location, $engage = false, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        return array_values(array_filter($performers, function (Character $performer) use ($theah) {
            if (count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) == 0)
            {
                return false;
            }
            return count($this->getValidTargets($theah, $performer)) > 0;
        }));
    }

    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $adjacentLocations = $theah->getAdjacentCityLocations($performer->Location, $includeHome = true);
        $targets = [];
        foreach ($adjacentLocations as $location)
        {
            $characters = $theah->getCharactersAtLocation($location);
            foreach ($characters as $character)
            {
                if ($character->ControllerId != $performer->ControllerId) continue;
                if (! ($character->hasTrait("Thug") || $character->hasTrait("Bodyguard"))) continue;
                $targets[$character->Id] = $character;
            }
        }
        return array_values($targets);
    }
}
