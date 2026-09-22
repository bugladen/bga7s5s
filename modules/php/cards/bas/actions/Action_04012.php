<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

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

class Action_04012 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Raven. Issue a Finesse Challenge.");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $raven = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($raven))
        {
            return false;
        }

        // WHY Engage printed: trichotomy (a) — only unengaged Raven is eligible.
        if (! $raven->canChallenge($theah) || $raven->Engaged || $raven->DashedFinesse)
        {
            return false;
        }

        return count($this->getOpposingTargets($theah, $raven)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $raven = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $raven->Id, "04012", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $raven = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $raven->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $raven->Location)
        {
            return [false, $game->translate("Target must be at Raven's location.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04012)
        {
            $raven = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $raven->Id;

            $targets = $this->getOpposingTargets($game->theah, $raven);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $targets));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04012)
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

            $raven = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $raven->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);
            // WHY RAVEN_CHALLENGE_TYPE: "Other characters cannot intervene" — Valeri/Torvo shape.
            // Engage printed → added to stIssueChallenge auto-engage list.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::RAVEN_CHALLENGE_TYPE);

            $transitionEvent = EventFactory::createTransitionEvent($raven->ControllerId, $raven->Id, "04012_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    private function getOpposingTargets(Theah $theah, Character $raven): array
    {
        $characters = $theah->getOpposingCharactersAtLocation($raven->Location, $raven->ControllerId);
        return array_values($characters);
    }
}
