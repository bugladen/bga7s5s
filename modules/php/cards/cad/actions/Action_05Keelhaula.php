<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Surprise Boarding: Covert Pirate Action - Your performer issues a [Combat] challenge
// to target opposing character.
class Action_05Keelhaula extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert Pirate: Issue Combat Challenge";
        $this->RequiresPerformerSelected = true;
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
        // WHY not RiskCityAction: the heading says "Covert Pirate Action", not "City Action".
        // City comes from Covert (uncontrolled City location), checked explicitly below.
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah)
            {
                if (! $performer->hasTrait("Pirate")
                    || ! $theah->cardInCity($performer)
                    || ! $performer->canChallenge($theah)
                    || $performer->DashedCombat)
                {
                    return false;
                }

                // WHY: Covert abilities may only be used at uncontrolled locations (Valeri / Overcoat).
                if ($theah->getCityLocation($performer->Location)->isControlled())
                {
                    return false;
                }

                // WHY no !Engaged gate: print has no Engage cost. canChallenge does not
                // check Engaged. Engaged Pirates stay eligible (Courageous / Arrogant).
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
            // WHY KEELHAUL_PIRATE_CHALLENGE_TYPE (off stIssueChallenge auto-engage):
            // print has no Engage cost. NORMAL would auto-engage. Engaged Pirates stay
            // eligible. Sanjay trichotomy (c) — dedicated type solely to avoid NORMAL.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::KEELHAUL_PIRATE_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Keelhaula", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }
}
