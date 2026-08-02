<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04009 extends RiskAction
{
    // WHY: Stash our performer before CHOSEN_PERFORMER is overwritten with the enemy challenger.
    public int $DefenderId = 0;

    // WHY: Duelist first-combat-card +1 Riposte. Risk is in discard during the duel;
    // discard is loaded by buildCity so this Action still receives calc events.
    public int $FirstCombatCardRiposteCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Opponent's Character Issues a Combat Challenge");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getChallengingCharactersForOpponent(Theah $theah, Character $defender, int $opponentId): array
    {
        $characters = $theah->getCharactersAtLocation($defender->Location);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->ControllerId == $opponentId
                && $character->canChallenge($theah)
        ));
    }

    /**
     * @return list<int>
     */
    private function getValidOpponentIds(Theah $theah, Character $defender): array
    {
        $opponentIds = [];
        $characters = $theah->getCharactersAtLocation($defender->Location);
        foreach ($characters as $character)
        {
            if ($character->ControllerId == $defender->ControllerId || $character->ControllerId == 0)
            {
                continue;
            }
            if (! $character->canChallenge($theah))
            {
                continue;
            }
            $opponentIds[$character->ControllerId] = $character->ControllerId;
        }

        return array_values($opponentIds);
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah) {
                // WHY En Garde Action label: performer must already be ready — not an Engage cost.
                if ($performer->Engaged)
                {
                    return false;
                }

                return count($this->getValidOpponentIds($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    private function markOwnerUpdated(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function beginOpponentCharacterChoice(Game $game, int $opponentId): void
    {
        $owner = $this->getOwningCard($game->theah);
        $game->globals->set(Game::CHOSEN_OPPONENT, $opponentId);

        $transition = EventFactory::createTransitionEvent($opponentId, $owner->Id, "04009_2", $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function setupChallengeFromChosenChallenger(Game $game, Character $challenger): void
    {
        $defender = $game->theah->getCharacterById($this->DefenderId);
        if ($defender === null)
        {
            throw new UserException($game->translate("Performer not found."));
        }

        $owner = $this->getOwningCard($game->theah);

        $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
        $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
        $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
        // WHY custom type: forced enemy challenge — keep off stIssueChallenge auto-engage
        // (Defending Honor / Sanjay). Engaging the opponent for free would be a bonus not printed.
        $game->globals->set(Game::CHALLENGE_TYPE, Game::RATTLE_THE_RIGGING_CHALLENGE_TYPE);

        if ($defender->hasTrait("Duelist"))
        {
            $this->FirstCombatCardRiposteCharacterId = $defender->Id;
            $owner->IsUpdated = true;
        }

        $game->notify->all("message", clienttranslate('${owner_inject_code}: ${challenger_inject_code} will issue a Combat challenge to ${defender_inject_code}.'), [
            "owner_inject_code" => $owner->getInjectCode(),
            "challenger_inject_code" => $challenger->getInjectCode(),
            "defender_inject_code" => $defender->getInjectCode(),
        ]);

        // createActionResolvedEvent() is called when the challenge is resolved

        $transition = EventFactory::createTransitionEvent($challenger->ControllerId, $owner->Id, "04009_3", $this->Id);
        $game->theah->queueEvent($transition);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $defenderId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $defender = $event->theah->getCharacterById($defenderId);
            if ($defender === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $this->DefenderId = $defender->Id;
            $owner->IsUpdated = true;

            $opponentIds = $this->getValidOpponentIds($event->theah, $defender);
            if (count($opponentIds) == 0)
            {
                throw new UserException($game->translate("No opponent has a character that can challenge your performer."));
            }

            // Single valid opponent: still "Target opponent", but skip the button state.
            if (count($opponentIds) == 1)
            {
                $this->beginOpponentCharacterChoice($game, $opponentIds[0]);
                return;
            }

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04009", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelCalculateCombatCardStats
            && $this->FirstCombatCardRiposteCharacterId != 0
            && $event->actorId == $this->FirstCombatCardRiposteCharacterId)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s: first combat card gains +1 Riposte"),
                $owner->getInjectCode()
            );
            $event->addRiposte(1);
            $this->FirstCombatCardRiposteCharacterId = 0;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd && $this->FirstCombatCardRiposteCharacterId != 0)
        {
            $this->FirstCombatCardRiposteCharacterId = 0;
            $this->markOwnerUpdated($event->theah);
        }

        // WHY: Challenge cancelled / refused with no duel — clear unused Riposte arm.
        // Gate on !IN_DUEL so a mid-duel ActionResolved (if any) cannot wipe the arm
        // before the Duelist's first combat card. At duel end IN_DUEL is already false;
        // EventDuelEnd also clears.
        if ($event instanceof EventActionResolved
            && $this->FirstCombatCardRiposteCharacterId != 0
            && ! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            $this->FirstCombatCardRiposteCharacterId = 0;
            $this->markOwnerUpdated($event->theah);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $defender = $game->theah->getCharacterById($this->DefenderId);
        $args['performerId'] = $this->DefenderId;

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04009)
        {
            $opponents = [];
            if ($defender !== null)
            {
                foreach ($this->getValidOpponentIds($game->theah, $defender) as $opponentId)
                {
                    $opponents[] = [
                        'id' => $opponentId,
                        'name' => $game->getPlayerNameById($opponentId),
                    ];
                }
            }
            $args['opponents'] = $opponents;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04009_2)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $ids = [];
            if ($defender !== null)
            {
                $challengers = $this->getChallengingCharactersForOpponent($game->theah, $defender, $opponentId);
                $ids = array_map(fn(Character $c) => $c->Id, $challengers);
            }
            $args['ids'] = $ids;
            $args['opponentName'] = $game->getPlayerNameById($opponentId);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04009)
        {
            $defender = $game->theah->getCharacterById($this->DefenderId);
            if ($defender === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $validOpponentIds = $this->getValidOpponentIds($game->theah, $defender);
            if (! in_array($id, $validOpponentIds))
            {
                throw new UserException($game->translate("Invalid opponent."));
            }

            $this->beginOpponentCharacterChoice($game, $id);
            $game->gamestate->nextState("opponentChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04009_2)
        {
            $challenger = $game->theah->getCharacterById($id);
            if ($challenger === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            $defender = $game->theah->getCharacterById($this->DefenderId);
            if ($defender === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $valid = $this->getChallengingCharactersForOpponent($game->theah, $defender, $opponentId);
            $validIds = array_map(fn(Character $c) => $c->Id, $valid);
            if (! in_array($challenger->Id, $validIds))
            {
                throw new UserException($game->translate("Character must be yours, opposing the performer, and able to challenge."));
            }

            $this->setupChallengeFromChosenChallenger($game, $challenger);
            $game->gamestate->nextState("characterChosen");
            return;
        }
    }
}
