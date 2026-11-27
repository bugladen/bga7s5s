<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01115 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Adjacent Enemy Character to Performer's Location");
        $this->RequiresPerformerSelected = true;
    }

    private function getCharactersWithOpposingCharactersAtAdjacentLocations(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $charactersWithOpposingCharactersAtAdjacentLocations = [];
        foreach ($characters as $character)
        {
            $adjacentLocations = $theah->getAdjacentCityLocations($character->Location, $includeHome = true);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId));
                if (count($opposingCharacters) > 0)
                {
                    $charactersWithOpposingCharactersAtAdjacentLocations[$character->Id] = $character;
                }
            }
        }
        return array_values($charactersWithOpposingCharactersAtAdjacentLocations);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $characters = $this->getCharactersWithOpposingCharactersAtAdjacentLocations($playerId, $theah);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getCharactersWithOpposingCharactersAtAdjacentLocations($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01115", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $character = $theah->getCharacterById($event->targetId);
            // WHY CHOSEN_PERFORMER: still set while the targeting event dispatches.
            $performer = $theah->getCharacterById((int)$theah->game->globals->get(Game::CHOSEN_PERFORMER));
            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, $performer->Location, $engage = false, $owner->Id, $this->Id);
            $moveEvent->batchId = $event->batchId ?? $theah->game->getNextEventBatchId();
            $theah->eventCheck($moveEvent);
            $theah->queueEvent($moveEvent);

            // ActionResolved stays unbatched (01160 convention) so a batch strip cannot eat it.
            $theah->queueEvent(EventFactory::createActionResolvedEvent($performer->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01115)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $performer = $game->theah->getCharacterById($performerId);
            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = true);
            $adjacentCharacters = [];
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId));
                if (count($opposingCharacters) > 0)
                {
                   $adjacentCharacters = array_merge($adjacentCharacters, $opposingCharacters);
                }
            }
            $args['ids'] = array_map(fn($character) => $character->Id, array_values($adjacentCharacters));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot move your own character.")];
        }

        if ($character->Location == $performer->Location)
        {
            return [false, $game->translate("Character is already at the performer's location.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01115)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            // WHY: Fire targeting alone here. The move + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($performer->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}