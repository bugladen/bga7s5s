<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Surprise Boarding: Covert Action - If you aren't the first player • Your performer
// issues a [Finesse] challenge to target opposing character.
class Action_05Keelhaulb extends RiskAction implements IAbilityThatTargetsCharacters, IAbilityThatDependsOnNotBeingFirstPlayer
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Issue Finesse Challenge (Not First Player)";
        $this->RequiresPerformerSelected = true;
    }

    private function isFirstPlayer(int $playerId, Theah $theah): bool
    {
        // WHY: Lorenzo OVERRIDE_AS_NOT_FIRST_PLAYER — treat as not first (01103b / 01112b).
        $forcedNotFirstPlayer = $theah->game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
        return $theah->game->globals->get(Game::FIRST_PLAYER) == $playerId && ! $forcedNotFirstPlayer;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        // WHY not RiskCityAction: the heading says "Covert Action", not "City Action".
        // City comes from Covert (uncontrolled City location), checked explicitly below.
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah)
            {
                if (! $theah->cardInCity($performer)
                    || ! $performer->canChallenge($theah)
                    || $performer->DashedFinesse)
                {
                    return false;
                }

                // WHY: Covert abilities may only be used at uncontrolled locations (Valeri / Overcoat).
                if ($theah->getCityLocation($performer->Location)->isControlled())
                {
                    return false;
                }

                // WHY no !Engaged gate: print has no Engage cost. Engaged characters stay
                // eligible (Courageous / Arrogant / Keelhaula).
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

        // WHY: Bullet-If "If you aren't the first player" is an availability filter.
        if ($this->isFirstPlayer($playerId, $theah))
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
            return [false, "Performer not found."];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, "Target must be at your performer's location."];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            // WHY KEELHAUL_CHALLENGE_TYPE (off stIssueChallenge auto-engage): print has
            // no Engage cost. NORMAL would auto-engage. Separate from Keelhaula so a
            // later refuse/intervene correlator cannot confuse Combat vs Finesse.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::KEELHAUL_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Keelhaulb", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }
}
