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

class Action_03037 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue an Influence Challenge if Opponent Has Fewer Cards in Hand");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $sanjay = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($sanjay))
        {
            return false;
        }

        // WHY no !Engaged gate: this action does not Engage Sanjay, so engaged
        // Sanjay remains eligible.
        if (! $sanjay->canChallenge($theah) || $sanjay->DashedInfluence)
        {
            return false;
        }

        return count($this->getEligibleTargets($theah, $sanjay)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $sanjay = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $sanjay->Id, "03037", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $sanjay = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $sanjay->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $sanjay->Location)
        {
            return [false, $game->translate("Target must be at Sanjay's location.")];
        }

        if (! $this->opponentHasFewerCardsInHand($game->theah, $sanjay->ControllerId, $character->ControllerId))
        {
            return [false, $game->translate("Their controller must have fewer cards in hand than you.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03037)
        {
            $sanjay = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $sanjay->Id;

            $targets = $this->getEligibleTargets($game->theah, $sanjay);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $targets));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03037)
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

            $sanjay = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $sanjay->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);
            // WHY SANJAY_CHALLENGE_TYPE (kept out of stIssueChallenge's auto-engage
            // list): this is not a basic challenge and the card does not Engage
            // Sanjay. NORMAL_CHALLENGE_TYPE would auto-engage him.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::SANJAY_CHALLENGE_TYPE);

            $transitionEvent = EventFactory::createTransitionEvent($sanjay->ControllerId, $sanjay->Id, "03037_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    /**
     * Opposing characters at Sanjay's location whose controller has fewer
     * cards in hand than Sanjay's controller.
     */
    private function getEligibleTargets(Theah $theah, Character $sanjay): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($sanjay->Location, $sanjay->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn($character) => $this->opponentHasFewerCardsInHand($theah, $sanjay->ControllerId, $character->ControllerId)
        ));
    }

    private function opponentHasFewerCardsInHand(Theah $theah, int $sanjayPlayerId, int $opponentPlayerId): bool
    {
        $sanjayHand = count($theah->game->getGameDeckObject()->getPlayerHand($sanjayPlayerId));
        $opponentHand = count($theah->game->getGameDeckObject()->getPlayerHand($opponentPlayerId));
        return $opponentHand < $sanjayHand;
    }
}
