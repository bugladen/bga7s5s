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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01162 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Target Character to an Adjacent City Location");
        $this->RequiresPerformerSelected = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($theah->getCharactersInPlay()) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01162", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // Keep CHOSEN_TARGET in sync if a reaction redirects the target via EventCharacterTargeted
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHOSEN_TARGET, $event->targetId);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01162)
        {
            $characters = $game->theah->getCharactersInPlay();
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01162_2)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $args["targetId"] = $targetId;

            $adjacentLocations = $game->theah->getAdjacentCityLocations($target->Location, $includeHome = false);
            $args["locationIds"] = $adjacentLocations;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01162)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            $owner = $this->getOwningCard($game->theah);

            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($targetedEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01162_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01162_2)
        {
            $location = $ids[0];

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $locations = $game->theah->getAdjacentCityLocations($target->Location, $includeHome = false);
            if (! in_array($location, $locations))
            {
                throw new UserException(sprintf($game->translate('Location %s is not Adjacent to %s.'), $location, $target->Location));
            }

            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardMovingEvent($target->ControllerId, $target->Id, $target->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
