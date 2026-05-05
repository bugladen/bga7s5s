<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02036a extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate('Move opposing non-Pirate Home unless their controller discards');
    }

    /**
     * @return list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character>
     */
    private function eligibleTargetsAtLocation(Theah $theah, int $playerId, string $location): array
    {
        $opponents = $theah->getOpposingCharactersAtLocation($location, $playerId);
        $opponents = array_filter($opponents, fn ($c) => ! $c->hasTrait("Pirate"));

        return array_values($opponents);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $pirates = $theah->getCharactersInCityByPlayerId($playerId);
        $pirates = array_filter($pirates, fn ($c) => $c->hasTrait("Pirate"));
        foreach ($pirates as $pirate) {
            if (count($this->eligibleTargetsAtLocation($theah, $playerId, $pirate->Location)) > 0) {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_filter($performers, fn ($c) => $c->hasTrait("Pirate"));
        $performers = array_filter($performers, fn ($c) => count($this->eligibleTargetsAtLocation($theah, $playerId, $c->Location)) > 0);

        return array_values($performers);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02036", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02036) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;
            $args['ids'] = $performer
                ? array_map(fn ($c) => $c->Id, $this->eligibleTargetsAtLocation($game->theah, $performer->ControllerId, $performer->Location))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02036_2) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
            $args['characterId'] = (int) $game->globals->get(Game::CHOSEN_TARGET);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02036) {
            $character = $game->theah->getCharacterById($id);
            if ($character == null) {
                throw new UserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null) {
                throw new UserException($game->translate("Character not found"));
            }

            if ($character->ControllerId == $performer->ControllerId) {
                throw new UserException($game->translate("You cannot choose your own character"));
            }

            if ($character->Location != $performer->Location) {
                throw new UserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($character->hasTrait("Pirate")) {
                throw new UserException($game->translate("You cannot choose a Pirate"));
            }

            $allowed = array_map(fn ($c) => $c->Id, $this->eligibleTargetsAtLocation($game->theah, $performer->ControllerId, $performer->Location));
            if (! in_array($id, $allowed, true)) {
                throw new UserException($game->translate("Invalid character choice"));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $id);

            $transition = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "02036_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("targetChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02036_2) {
            $activeId = (int) $game->getActivePlayerId();
            $targetId = (int) $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null) {
                throw new UserException($game->translate("Character not found"));
            }

            if ($activeId !== $target->ControllerId) {
                throw new UserException($game->translate("It is not your turn to respond"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $activeId);
            $handIds = array_map(fn ($c) => $c->Id, $hand);
            if (! in_array($id, $handIds, true)) {
                throw new UserException($game->translate("Invalid card choice"));
            }

            $owner = $this->getOwningCard($game->theah);
            $schemeOwnerId = $owner->ControllerId;

            $discard = EventFactory::createCardDiscardedFromHandEvent($activeId, $id, $owner->Id);
            $game->theah->eventCheck($discard);
            $game->theah->queueEvent($discard);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($schemeOwnerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState('');
        }
    }

    public function actFromActionPass(Game $game, int $state, string $stateName = ''): void
    {
        parent::actFromActionPass($game, $state);

        if ($state != States::HIGH_DRAMA_PLAYER_TURN_02036_2) {
            return;
        }

        $activeId = (int) $game->getActivePlayerId();
        $targetId = (int) $game->globals->get(Game::CHOSEN_TARGET);
        $target = $game->theah->getCharacterById($targetId);
        if ($target === null) {
            throw new UserException($game->translate("Character not found"));
        }

        if ($activeId !== $target->ControllerId) {
            throw new UserException($game->translate("It is not your turn to respond"));
        }

        $owner = $this->getOwningCard($game->theah);
        $schemeOwnerId = $owner->ControllerId;

        $move = EventFactory::createCardMovingEvent($schemeOwnerId, $target->Id, $target->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
        $game->theah->eventCheck($move);
        $game->theah->queueEvent($move);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($schemeOwnerId);
        $game->theah->queueEvent($actionResolvedEvent);

        $game->gamestate->nextState('');
    }
}
