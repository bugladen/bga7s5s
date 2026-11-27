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

class Action_01081 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Characters");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        $result = parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck);
        if (!$result)
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($characters as $character)
        {
            if (! $character->Engaged)
            {
                continue;
            }
            $charactersAtLocation = $theah->getCharactersAtLocation($character->Location);
            $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->ControllerId != $playerId && $character->Engaged);
            if (count($charactersAtLocation) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, function($performer) use ($theah, $playerId) 
        {
            if (! $performer->Engaged)
            {
                return false;
            }
            $charactersAtLocation = $theah->getCharactersAtLocation($performer->Location);
            $opposing = array_filter($charactersAtLocation, fn($character) => $character->ControllerId != $playerId && $character->Engaged);
            return count($opposing) > 0;
        }));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '01081', $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $game = $theah->game;
            $owner = $this->getOwningCard($theah);
            $character = $theah->getCardById($event->targetId);
            $performer = $theah->getCardById($game->globals->get(Game::CHOSEN_PERFORMER));
            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            $engardeTarget = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $engardeTarget->batchId = $batchId;
            $theah->queueEvent($engardeTarget);

            $engardePerformer = EventFactory::createCardEngardedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $engardePerformer->batchId = $batchId;
            $theah->queueEvent($engardePerformer);

            // ActionResolved stays unbatched (01160 convention).
            $theah->queueEvent(EventFactory::createActionResolvedEvent($performer->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01081)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);
            $args['performerId'] = $performerId;

            $charactersAtLocation = $game->theah->getCharactersAtLocation($performer->Location);
            $charactersAtLocation = array_values(array_filter($charactersAtLocation, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId) && $character->Engaged));

            $args['characterIds'] = array_map(fn($character) => $character->Id, $charactersAtLocation);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCardById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Character cannot be owned by you.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        if (! $character->Engaged)
        {
            return [false, $game->translate("Character is already En Garded.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01081)
        {
            $character = $game->theah->getCardById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Fire targeting alone here; both En Gardes + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it). The performer's
            // En Garde is half of the printed effect (not a bullet cost), so it is gated too.
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}