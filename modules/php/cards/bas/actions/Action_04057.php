<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04057 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Challenge (Fewer Characters)");
        // WHY: Leader Action = hasTrait("Leader") performer gate — more than one Leader
        // can be in play (Bravos muster, etc.). Do not use getLeaderByPlayerId alone.
        $this->RequiresPerformerSelected = true;
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
                if (! $performer->hasTrait("Leader"))
                {
                    return false;
                }

                // WHY En Garde heading: precondition, not Engage cost.
                if ($performer->Engaged || ! $performer->canChallenge($theah))
                {
                    return false;
                }

                if (! $theah->cardInCity($performer))
                {
                    return false;
                }

                if (count($this->getChallengeableStats($performer)) == 0)
                {
                    return false;
                }

                return count($this->getValidTargets($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        if (! $this->controlsFewerCharactersThanAnOpponent($theah, $playerId))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        if ($performer === null)
        {
            return [false, $game->translate("Performer not found.")];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("Target must be a non-Leader.")];
        }

        $myCount = $this->countCharactersInPlay($game->theah, $performer->ControllerId);
        $theirCount = $this->countCharactersInPlay($game->theah, $character->ControllerId);
        if ($myCount >= $theirCount)
        {
            return [false, $game->translate("Target must be controlled by an opponent who controls more characters than you.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04057", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04057)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer?->Id;
            $args['stats'] = $performer !== null ? $this->getChallengeableStats($performer) : [];
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04057)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            if (! $performer->hasTrait("Leader"))
            {
                throw new UserException($game->translate("Performer must be a Leader."));
            }

            if ($performer->Engaged || ! $performer->canChallenge($game->theah))
            {
                throw new UserException($game->translate("Your performer cannot issue a challenge."));
            }

            $stat = $this->statFromChoiceId($id);
            if ($stat === null || ! $this->canChallengeWithStat($performer, $stat))
            {
                throw new UserException($game->translate("Choose Combat, Finesse, or Influence."));
            }

            if (! $this->controlsFewerCharactersThanAnOpponent($game->theah, $owner->ControllerId))
            {
                throw new UserException($game->translate("You must control fewer characters than an opponent."));
            }

            if (count($this->getValidTargets($game->theah, $performer)) == 0)
            {
                throw new UserException($game->translate("No valid target characters."));
            }

            // WHY HONORABLE_CHALLENGE_TYPE: (1) off stIssueChallenge auto-engage — print has no
            // Engage cost; En Garde is only a precondition (not basic Challenge engage);
            // (2) not NORMAL — choose-stat already committed after pay, so Back on
            // choose-target must stay hidden.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::HONORABLE_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, $stat);

            $transition = EventFactory::createTransitionEvent(
                $performer->ControllerId,
                $owner->Id,
                "04057_2",
                $this->Id
            );
            $game->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved

            $game->gamestate->nextState("statChosen");
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        $myCount = $this->countCharactersInPlay($theah, $performer->ControllerId);

        return array_values(array_filter(
            $opposing,
            function (Character $character) use ($theah, $myCount) {
                if ($character->hasTrait("Leader"))
                {
                    return false;
                }

                return $myCount < $this->countCharactersInPlay($theah, $character->ControllerId);
            }
        ));
    }

    /**
     * @return list<string>
     */
    private function getChallengeableStats(Character $performer): array
    {
        $stats = [];
        foreach ([Game::STAT_COMBAT, Game::STAT_FINESSE, Game::STAT_INFLUENCE] as $stat)
        {
            if ($this->canChallengeWithStat($performer, $stat))
            {
                $stats[] = $stat;
            }
        }

        return $stats;
    }

    private function canChallengeWithStat(Character $performer, string $stat): bool
    {
        // WHY: reuse canPressure dashed-stat gates — same printed-dashed rule for challenges.
        return $performer->canPressure($stat);
    }

    private function controlsFewerCharactersThanAnOpponent(Theah $theah, int $playerId): bool
    {
        $myCount = $this->countCharactersInPlay($theah, $playerId);
        $players = $theah->game->loadPlayersBasicInfos();

        foreach ($players as $opponentId => $info)
        {
            if ((int)$opponentId === $playerId)
            {
                continue;
            }

            if ($myCount < $this->countCharactersInPlay($theah, (int)$opponentId))
            {
                return true;
            }
        }

        return false;
    }

    private function countCharactersInPlay(Theah $theah, int $playerId): int
    {
        return count($theah->getCharactersInPlayByPlayerId($playerId));
    }

    private function statFromChoiceId(int $id): ?string
    {
        return match ($id)
        {
            1 => Game::STAT_COMBAT,
            2 => Game::STAT_FINESSE,
            3 => Game::STAT_INFLUENCE,
            default => null,
        };
    }
}
