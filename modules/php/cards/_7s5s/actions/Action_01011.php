<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01011 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Challenge Character Opposing an Adjacent Red Hand");
    }

    private function getTargetCharacters(int $playerId, Theah $theah): array
    {
        $servo = $this->getOwningCharacter($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($servo->Location, $includeHome = false);

        $thugs = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $thugs = array_values(array_filter($thugs, fn($character) => $character->hasTrait("Red Hand") && in_array($character->Location, $adjacentLocations)));
        $targetCharacters = [];
        foreach ($thugs as $thug)
        {
            $characters = $theah->getOpposingCharactersAtLocation($thug->Location, $playerId);
            foreach ($characters as $c)
            {
                $targetCharacters[$c->Id] = $c;
            }
        }
        $targetCharacters = array_values($targetCharacters);

        // WHY: Combat challenge after move. Drop Envoy/Patricia-banned targets so Servo
        // never moves onto ActivateTechnique with Back locked and an unissuable challenge.
        return array_values(array_filter(
            $targetCharacters,
            fn($c) => $theah->challengeCanBeIssued($servo->Id, $c->Id, Game::STAT_COMBAT)
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $servo = $this->getOwningCharacter($theah);
        if ($servo->Engaged)
        {
            return false;
        }

        if (! $theah->cardInCity($servo))
        {
            return false;
        }

        // WHY: Same list as args / act validation — includes ChallengeIssued legality.
        return count($this->getTargetCharacters($playerId, $theah)) > 0;
    }

    // WHY: Move-then-challenge. Soline can flee after Servo lands; Diplomatic Envoy /
    // Patricia can ban Combat. stTechniqueAvailable skips when false (Valeri/Andriana shape).
    public function shouldIssueChallenge(Game $game): bool
    {
        $servo = $this->getOwningCharacter($game->theah);
        $targetId = (int) $game->globals->get(Game::CHOSEN_TARGET);
        $target = $game->theah->getCharacterById($targetId);

        if ($servo === null || $target === null)
        {
            return false;
        }

        if ($target->Location !== $servo->Location)
        {
            return false;
        }

        return $game->theah->challengeCanBeIssued($servo->Id, $target->Id);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $servo = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($servo->ControllerId, $servo->Id, "01011", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01011)
        {
            $performerId = $game->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            $args["performerId"] = $performerId;

            $characters = $this->getTargetCharacters($performer->ControllerId, $game->theah);
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01011)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            $availableTargets = $this->getTargetCharacters($game->getActivePlayerId(), $game->theah);
            if (! in_array($target, $availableTargets))
            {
                throw new UserException($game->translate("Character is not available to be challenged."));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::SERVO_SCARPA_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $servo = $this->getOwningCharacter($game->theah);

            $moveEvent = EventFactory::createCardMovingEvent($servo->ControllerId, $servo->Id, $servo->Location, $target->Location, $engage = false, $servo->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $transition = EventFactory::createTransitionEvent($servo->ControllerId, $servo->Id, "01011_2", $this->Id);
            $game->theah->queueEvent($transition);

            //Always available to use
            $this->setUsed($game->theah, false);

            $game->gamestate->nextState("opposingCharacterChosen");
        }
    }
}