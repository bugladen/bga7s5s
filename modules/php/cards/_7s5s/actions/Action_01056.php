<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01056 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Challenge");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->canChallenge());

        $charactersThatCanChallenge = [];
        foreach ($characters as $character)
        {
            $opponents = $theah->getCharactersAtLocation($character->Location);
            $opponents = array_filter($opponents, fn($opponent) => $opponent->isNotControlledByPlayer($playerId));
            if (count($opponents) > 0)
            {
                $charactersThatCanChallenge[] = $character;
            }
        }

        return count($charactersThatCanChallenge) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($character) => $character->canChallenge()));

        $charactersThatCanChallenge = [];
        foreach ($performers as $performer)
        {
            $opponents = $theah->getCharactersAtLocation($performer->Location);
            $opponents = array_filter($opponents, fn($opponent) => $opponent->isNotControlledByPlayer($playerId));
            if (count($opponents) > 0)
            {
                $charactersThatCanChallenge[] = $performer;
            }
        }

        return $charactersThatCanChallenge;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '01056', $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01056)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $opponents = $game->theah->getCharactersAtLocation($performer->Location);
            $opponents = array_values(array_filter($opponents, fn($opponent) => $opponent->isNotControlledByPlayer($game->getActivePlayerId())));
            $args['characterIds'] = array_map(fn($opponent) => $opponent->Id, $opponents);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01056)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found."));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($target->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot challenge a character that is controlled by you."));
            }

            if ($target->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("You cannot challenge a character that is not at the same location as you."));
            }

            $game->notify->all("message", clienttranslate('${player_name} chose ${performer_inject_code} to confront ${character_inject_code}.'), [
                'player_name' => $game->getPlayerNameById($performer->ControllerId),
                'performer_inject_code' => $performer->getInjectCode(),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            $owner = $this->getOwningCard($game->theah);
            $transition = EventFactory::createTransitionEvent($target->ControllerId, $owner->Id, '01056_2', $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01056_2)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $owner = $this->getOwningCard($game->theah);

            // Move Home
            if ($id == 1)
            {
                $game->notify->all("message", clienttranslate('${player_name} chose to move ${target_inject_code} home.'), [
                    'player_name' => $game->getPlayerNameById($target->ControllerId),
                    'target_inject_code' => $target->getInjectCode(),
                ]);

                $moveEvent = EventFactory::createCardMovedEvent($target->ControllerId, $target->Id, $target->Location, Game::LOCATION_PLAYER_HOME, $engage = true, $owner->Id);
                $game->theah->queueEvent($moveEvent);

            }

            // Continue with Challenge
            if ($id == 2)
            {
                $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
                $performer = $game->theah->getCharacterById($performerId);

                $game->notify->all("message", clienttranslate('${player_name} chose to continue with Challenge.'), [
                    'player_name' => $game->getPlayerNameById($target->ControllerId),
                ]);

                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE);

                $transition = EventFactory::createTransitionEvent($performer->ControllerId, $owner->Id, '01056_3', $this->Id);
                $game->theah->queueEvent($transition);
            }

            $game->gamestate->nextState();
        }
    }
}