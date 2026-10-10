<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01172 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Target Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer')));
        foreach ($performers as $performer)
        {
            $characters = $theah->getCharactersInPlay();
            $characters = array_filter($characters, fn($character) => $character->Location != $performer->Location);
            if (count($characters) > 0)
            {
                return true;
            }
        }
        return false;        
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer')));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01172", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01172)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersInPlay();
            $characters = array_filter($characters, fn($character) => $character->Location != $performer->Location);
            $args["ids"] = array_map(fn($character) => $character->Id, array_values($characters));
        }
        
        return $args;
    }
    
    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performer->Id)
        {
            return [false, $game->translate("Target character is the same as the performer.")];
        }

        if ($character->Location == $performer->Location)
        {
            return [false, $game->translate("Target character is at the same location as the performer.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01172)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

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
            
            $owner = $this->getOwningCard($game->theah);

            // WHY: Start before wound so Torsten (Reaction_01122) can stack cancel before the
            // performer wound processes. Shared batchId — cancel's deleteEventBatch strips the
            // wound (it does not target Torsten, so deleteEventsTargetingCard alone misses it).
            // Played after ActionResolved — Cesca journal 2026-09-01-01.
            $batchId = $game->getNextEventBatchId();

            $sorcererAbilityStartedEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
            $sorcererAbilityStartedEvent->batchId = $batchId;
            $game->theah->queueEvent($sorcererAbilityStartedEvent);

            if (! $performer->hasTrait('Strega'))
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $woundEvent->batchId = $batchId;
                $game->theah->queueEvent($woundEvent);
            }

            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $target->Id, $target->Location, $performer->Location, false, $owner->Id, $this->Id);
            $moveEvent->batchId = $batchId;
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $actionResolvedEvent->batchId = $batchId;
            $game->theah->queueEvent($actionResolvedEvent);

            $sorcererAbilityPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
            $sorcererAbilityPlayedEvent->batchId = $batchId;
            $game->theah->queueEvent($sorcererAbilityPlayedEvent);

            $game->gamestate->nextState();
        }
    }
}
