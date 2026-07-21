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

class Action_03003 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Your Thug Issues a Combat Challenge");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $don = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($don))
        {
            return false;
        }

        $thugs = $this->getAvailableThugs($theah, $don);
        foreach ($thugs as $thug)
        {
            if (count($theah->getOpposingCharactersAtLocation($thug->Location, $thug->ControllerId)) > 0)
            {
                return true;
            }
        }
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $don = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $don->Id, "03003", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer == null)
        {
            return [false, $game->translate("Performer not chosen.")];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at the performer's location.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03003)
        {
            $don = $this->getOwningCharacter($game->theah);
            $args['donId'] = $don->Id;

            $thugs = $this->getAvailableThugs($game->theah, $don);
            $args['ids'] = array_values(array_map(fn($thug) => $thug->Id, $thugs));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03003_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
            $args['performerId'] = $performerId;

            $performer = $game->theah->getCharacterById($performerId);
            if ($performer != null)
            {
                $targets = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
                $args['ids'] = array_values(array_map(fn($character) => $character->Id, $targets));
            }
            else
            {
                $args['ids'] = [];
            }
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03003)
        {
            $don = $this->getOwningCharacter($game->theah);
            $thug = $game->theah->getCharacterById($id);
            if ($thug == null)
            {
                throw new UserException($game->translate("Invalid Thug."));
            }

            $availableThugs = $this->getAvailableThugs($game->theah, $don);
            $availableIds = array_map(fn($t) => $t->Id, $availableThugs);
            if (! in_array($thug->Id, $availableIds))
            {
                throw new UserException($game->translate("Chosen character is not an eligible Thug."));
            }

            if (count($game->theah->getOpposingCharactersAtLocation($thug->Location, $thug->ControllerId)) == 0)
            {
                throw new UserException($game->translate("No opposing character at the Thug's location."));
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $thug->Id);

            $game->notify->all("message", clienttranslate('${don_inject_code}: ${player_name} chose ${thug_inject_code} to issue a Combat Challenge.'), [
                "don_inject_code" => $don->getInjectCode(),
                "player_name" => $game->getPlayerNameById($don->ControllerId),
                "thug_inject_code" => $thug->getInjectCode(),
            ]);

            $game->gamestate->nextState("thugChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03003_2)
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

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::DON_CONSTANZO_CHALLENGE_TYPE);

            // WHY engage here instead of via stIssueChallenge's auto-engage
            // list: engaged Thugs are eligible performers for this action
            // (card text doesn't print "Engage your Thug"). Firing the
            // auto-engage on an already-engaged Thug would re-emit
            // EventCardEngaged, which downstream reactions (e.g. Vittoria's
            // "instead of me" swap) treat as a fresh engagement. So we
            // emit the engage event only when the Thug isn't already
            // engaged, and we keep DON_CONSTANZO out of the auto-engage list.
            if (! $performer->Engaged)
            {
                $don = $this->getOwningCharacter($game->theah);
                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $don->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "03003_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    /**
     * Thugs the player controls at Don's location.
     *
     * WHY engaged Thugs are eligible: card text doesn't say "Engage your Thug"
     * — the Thug just "issues a Combat challenge". An already-engaged Thug
     * has effectively already paid the engagement cost so it can still
     * perform this action. `canChallenge($theah)` covers the hard-ban cases (e.g.
     * Sigurd Ulfsen's permanent "cannot challenge"); engagement is handled
     * separately in step 2 of the action.
     */
    private function getAvailableThugs(Theah $theah, Character $don): array
    {
        $atLocation = $theah->getCharactersAtLocationByPlayerId($don->Location, $don->ControllerId);
        $thugs = array_filter($atLocation, fn($c) => $c->hasTrait("Thug") && $c->canChallenge($theah));
        return array_values($thugs);
    }
}
