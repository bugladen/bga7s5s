<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02051 extends RiskCityAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Performer, En Garde Target Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $sorcerers = array_filter($characters, fn($c) => $c->hasTrait("Sorcerer", $owner) && !$c->Engaged);

        foreach ($sorcerers as $sorcerer)
        {
            $atLocation = $theah->getCharactersAtLocation($sorcerer->Location);
            $targets = array_filter($atLocation, fn($c) => $c->Id != $sorcerer->Id && $c->Engaged);
            if (count($targets) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $owner = $this->getOwningCard($theah);
        $sorcerers = array_values(array_filter($performers, fn($c) => $c->hasTrait("Sorcerer", $owner) && !$c->Engaged));

        $eligible = [];
        foreach ($sorcerers as $sorcerer)
        {
            $atLocation = $theah->getCharactersAtLocation($sorcerer->Location);
            $targets = array_filter($atLocation, fn($c) => $c->Id != $sorcerer->Id && $c->Engaged);
            if (count($targets) > 0)
            {
                $eligible[] = $sorcerer;
            }
        }

        return $eligible;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "02051", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $game = $theah->game;
            $owner = $this->getOwningCard($theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $theah->getCharacterById($performerId);
            $target = $theah->getCharacterById($event->targetId);
            if ($owner === null || $performer === null || $target === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            $theah->queueEvent(EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId));

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $batchId;
            $theah->queueEvent($engageEvent);

            $engardeEvent = EventFactory::createCardEngardedEvent($target->ControllerId, $target->Id, $owner->Id, $this->Id);
            $engardeEvent->batchId = $batchId;
            $theah->queueEvent($engardeEvent);

            $theah->queueEvent(EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $target->Id, $target->Location));

            // ActionResolved stays unbatched (01160 convention).
            $theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02051)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $atLocation = $game->theah->getCharactersAtLocation($performer->Location);
            $targets = array_values(array_filter($atLocation, fn($c) => $c->Id != $performerId && $c->Engaged));
            $args["ids"] = array_map(fn($c) => $c->Id, $targets);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performerId)
        {
            return [false, $game->translate("You cannot target the performer.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the performer's location.")];
        }

        if (!$character->Engaged)
        {
            return [false, $game->translate("Character is not engaged.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02051)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Sending the Risk to The Locker stays here, unbatched, and is NOT gated on
            // targeting. The Risk is already parked in Purgatory; the Locker move is what takes it
            // out, so a canceled hook must not strand it (Matchlock 01156 discard seat).
            $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $owner->Id);
            $game->theah->queueEvent($lockerEvent);

            // WHY: Fire targeting alone here. Sorcerer start, Engage performer, En Garde, Sorcerer
            // played and ActionResolved emit from handleEvent only when EventCharacterTargeted
            // survives (Cloud Bottle / UL cancel it). Engage performer is gated with the effect so a
            // canceled hook does not leave a pure cost; Start/Played gated so a cancel never emits a
            // dangling Start (Reaction_02001 seat).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
