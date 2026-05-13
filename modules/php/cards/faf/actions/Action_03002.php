<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03002 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Aja. Issue a Combat Challenge.");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $aja = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($aja))
        {
            return false;
        }

        if (! $aja->canChallenge() || $aja->Engaged)
        {
            return false;
        }

        return count($this->getOpposingTargets($theah, $aja)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $aja = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $aja->Id, "03002", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $aja = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $aja->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $aja->Location)
        {
            return [false, $game->translate("Target must be at Aja's location.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03002)
        {
            $aja = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $aja->Id;

            $targets = $this->getOpposingTargets($game->theah, $aja);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $targets));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03002)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $aja = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $aja->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::AJA_CHALLENGE_TYPE);

            $transitionEvent = EventFactory::createTransitionEvent($aja->ControllerId, $aja->Id, "03002_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    private function getOpposingTargets(Theah $theah, Character $aja): array
    {
        $characters = $theah->getOpposingCharactersAtLocation($aja->Location, $aja->ControllerId);
        return array_values($characters);
    }
}
