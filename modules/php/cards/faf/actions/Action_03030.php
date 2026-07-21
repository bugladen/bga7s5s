<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03030 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Duelist Issues Combat Challenge");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Diplomat")
                && ! $performer->Engaged
                && count($this->getAvailableDuelists($theah, $performer)) > 0
                && count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $diplomatId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
            $diplomat = $event->theah->getCharacterById($diplomatId);

            if ($diplomat === null || ! $diplomat->hasTrait("Diplomat"))
            {
                throw new UserException($game->translate("Performer must be a Diplomat."));
            }

            if (! $this->hasEligibleDuelistWithTarget($event->theah, $diplomat))
            {
                throw new UserException($game->translate("No eligible Duelist with a valid target at this location."));
            }

            // WHY: preserve the engaged Diplomat while CHOSEN_PERFORMER later becomes the Duelist challenger.
            $game->globals->set(Game::CHOSEN_CARD, $diplomatId);

            if (! $diplomat->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($diplomat->ControllerId, $diplomat->Id, $owner->Id, $this->Id);
                $event->theah->queueEvent($engageEvent);
            }

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03030", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventGenerateChallengeThreat)
        {
            $challengeType = $event->theah->game->globals->get(Game::CHALLENGE_TYPE);
            if ($challengeType == Game::SWORN_SWORDS_CHALLENGE_TYPE)
            {
                $owner = $this->getOwningCard($event->theah);
                $event->actorThreat += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Adds 1 Threat to your participant when the challenge is accepted."),
                    $owner->getInjectCode()
                );
            }
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $duelistId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
        $duelist = $game->theah->getCharacterById($duelistId);

        if ($duelist === null)
        {
            return [false, $game->translate("Duelist not chosen.")];
        }

        if ($character->ControllerId == $duelist->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $duelist->Location)
        {
            return [false, $game->translate("Target must be at the Duelist's location.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03030)
        {
            $diplomatId = $game->globals->get(Game::CHOSEN_CARD, 0);
            $diplomat = $game->theah->getCharacterById($diplomatId);
            $args['diplomatId'] = $diplomatId;
            $args['ids'] = $diplomat !== null
                ? array_values(array_map(fn(Character $duelist) => $duelist->Id, $this->getAvailableDuelists($game->theah, $diplomat)))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03030_2)
        {
            $duelistId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
            $args['duelistId'] = $duelistId;

            $duelist = $game->theah->getCharacterById($duelistId);
            if ($duelist !== null)
            {
                $targets = $game->theah->getOpposingCharactersAtLocation($duelist->Location, $duelist->ControllerId);
                $args['ids'] = array_values(array_map(fn(Character $character) => $character->Id, $targets));
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03030)
        {
            $owner = $this->getOwningCard($game->theah);
            $diplomatId = $game->globals->get(Game::CHOSEN_CARD, 0);
            $diplomat = $game->theah->getCharacterById($diplomatId);
            if ($diplomat === null)
            {
                throw new UserException($game->translate("Diplomat performer not found."));
            }

            $duelist = $game->theah->getCharacterById($id);
            if ($duelist === null)
            {
                throw new UserException($game->translate("Invalid Duelist."));
            }

            $availableDuelists = $this->getAvailableDuelists($game->theah, $diplomat);
            $availableIds = array_map(fn(Character $character) => $character->Id, $availableDuelists);
            if (! in_array($duelist->Id, $availableIds, true))
            {
                throw new UserException($game->translate("Chosen character is not an eligible Duelist at this location."));
            }

            if (count($game->theah->getOpposingCharactersAtLocation($duelist->Location, $duelist->ControllerId)) == 0)
            {
                throw new UserException($game->translate("No opposing character at the Duelist's location."));
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} chose ${duelist_inject_code} to issue a Combat challenge.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "duelist_inject_code" => $duelist->getInjectCode(),
            ]);

            $game->gamestate->nextState("duelistChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03030_2)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $duelistId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $duelist = $game->theah->getCharacterById($duelistId);

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::SWORN_SWORDS_CHALLENGE_TYPE);

            $transitionEvent = EventFactory::createTransitionEvent($duelist->ControllerId, $duelist->Id, "03030_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent() is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }
    }

    private function hasEligibleDuelistWithTarget(Theah $theah, Character $diplomat): bool
    {
        foreach ($this->getAvailableDuelists($theah, $diplomat) as $duelist)
        {
            if (count($theah->getOpposingCharactersAtLocation($duelist->Location, $duelist->ControllerId)) > 0)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Character>
     */
    private function getAvailableDuelists(Theah $theah, Character $diplomat): array
    {
        $atLocation = $theah->getCharactersAtLocationByPlayerId($diplomat->Location, $diplomat->ControllerId);

        return array_values(array_filter(
            $atLocation,
            fn(Character $character) => $character->hasTrait("Duelist") && $character->canChallenge($theah)
        ));
    }
}
