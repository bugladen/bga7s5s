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

class Action_01104 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Engage and Go Home with Opposing Character');
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => !$character->Engaged);

        foreach ($characters as $character)
        {
            $opponents = $theah->getOpposingCharactersAtLocation($character->Location, $playerId);
            if (count($opponents) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("Character cannot be owned by you")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = parent::getPerformersForAction($playerId, $theah);
        $characters = array_values(array_filter($characters, fn($character) => !$character->Engaged));

        $validPerformers = [];
        foreach ($characters as $character)
        {
            $opponents = $theah->getOpposingCharactersAtLocation($character->Location, $character->ControllerId);
            if (count($opponents) > 0)
            {
                $validPerformers[] = $character;
            }
        }

        return $validPerformers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01104", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        // WHY: Do not queue engage/Home beside the cancel hook. Unyielding Loyalty
        // stores only the event it intercepts and deleteEventBatchs the rest. When Night
        // of Drinking (01109) reverts UL, releaseEvent restored a single Beta engage —
        // Alpha never engaged/moved, Beta stayed at the location, Blood in the Water
        // wounded them. Gate effects on EventCharacterTargeted (Giacinto Action_01205 /
        // Come Hither Action_01162): UL holds the targeting event; Pass / NoD re-queue
        // it and this handler emits the full Amour package again.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);

            $character = $event->theah->getCharacterById($event->targetId);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($character === null || $performer === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            // WHY: Engage and go Home are separate clauses — queue Engage first, then move
            // with engage=false (Action_03cd01 / Reaction_02058 pattern). Lodestone blocks
            // opponent Home moves but the Engage clause still applies; tying engage to
            // CardMoved left Lodestone targets en garde when the move was swallowed.
            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $batchId;
            $event->theah->queueEvent($engageEvent);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $engageEvent->batchId = $batchId;
            $event->theah->queueEvent($engageEvent);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
            $moveEvent->batchId = $batchId;
            $event->theah->queueEvent($moveEvent);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $performer->Id, $performer->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
            $moveEvent->batchId = $batchId;
            $event->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01104)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01104)
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

            $owner = $this->getOwningCard($game->theah);

            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
