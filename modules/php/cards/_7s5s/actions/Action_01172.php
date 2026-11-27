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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
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

        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer', $owner)));
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
        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait('Sorcerer', $owner)));
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

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $this->resolveTargetedCharacter($event);
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

            // WHY: Unyielding Loyalty (and Hexenjagd) intercept EventCardMoving before the
            // hub move log. Name the target at pick time — same hole as challenge
            // announcePendingChallenge / Porté Travel / Action_01138.
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} has chosen to move ${character_inject_code} to ${performer_inject_code}\'s location.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
                "performer_inject_code" => $performer->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. SorcererAbilityStart, the performer wound (the "•"
            // cost), the move, ActionResolved and SorcererAbilityPlayed all emit from handleEvent
            // only when EventCharacterTargeted survives — a canceled targeting must not leave the
            // performer wounded for nothing or a dangling Start (Action_01012 seat).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }

    private function resolveTargetedCharacter(EventCharacterTargeted $event): void
    {
        $theah = $event->theah;
        $game = $theah->game;
        $owner = $this->getOwningCard($theah);
        $target = $theah->getCharacterById($event->targetId);
        // WHY CHOSEN_PERFORMER: still set while the targeting event dispatches.
        $performer = $theah->getCharacterById((int)$game->globals->get(Game::CHOSEN_PERFORMER));
        if ($owner === null || $target === null || $performer === null)
        {
            return;
        }

        // WHY: Start before wound so Torsten (Reaction_01122) can stack cancel before the
        // performer wound processes. Shared batchId — cancel's deleteEventBatch strips the
        // wound (it does not target Torsten, so deleteEventsTargetingCard alone misses it).
        // Played after ActionResolved — Cesca journal 2026-09-01-01.
        // The batch is the targeting event's batch so everything stays one unit.
        $batchId = $event->batchId ?? $game->getNextEventBatchId();

        $sorcererAbilityStartedEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
        $sorcererAbilityStartedEvent->batchId = $batchId;
        $theah->queueEvent($sorcererAbilityStartedEvent);

        if (! $performer->hasTrait('Strega', $owner))
        {
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $woundEvent->batchId = $batchId;
            $theah->queueEvent($woundEvent);
        }

        $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $target->Id, $target->Location, $performer->Location, false, $owner->Id, $this->Id);
        $moveEvent->batchId = $batchId;
        $theah->queueEvent($moveEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
        $actionResolvedEvent->batchId = $batchId;
        $theah->queueEvent($actionResolvedEvent);

        $sorcererAbilityPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
        $sorcererAbilityPlayedEvent->batchId = $batchId;
        $theah->queueEvent($sorcererAbilityPlayedEvent);
    }
}
