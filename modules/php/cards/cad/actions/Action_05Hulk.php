<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Hulk extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    // WHY: Adversary is unknown until the target is chosen. The Risk is in the
    // discard during the duel; buildCity reloads it, so this must persist (IsUpdated).
    public int $FirstCombatParryCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Engage, Wound Unless Berserker, Unrefusable Combat Challenge";
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

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, function (Character $performer) use ($theah) {
            if ($performer->Engaged || $performer->DashedCombat || ! $performer->canChallenge($theah))
            {
                return false;
            }

            return count($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId)) > 0;
        }));
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

    // WHY: Engage and the non-Berserker wound are both printed costs (before the bullet).
    // Pay them at announce so a later cancel still paid them. RAGE stays off
    // stIssueChallenge's auto-engage list and off stSetupChallenge (Unsanctioned
    // engages there) so a normal play does not engage twice.
    public function announceAction(Game $game): void
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        $owner = $this->getOwningCard($game->theah);

        if ($performer !== null && ! $performer->Engaged)
        {
            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
            $game->theah->queueEvent($engageEvent);
        }

        if ($performer !== null && $owner !== null && ! $performer->hasTrait("Berserker"))
        {
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $performer->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);
        }

        parent::announceAction($game);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            // WHY: Unrefusable Combat, Engage already paid. Not NORMAL (would engage
            // again and show Back). Not Aja/Celerity (those gate Finesse, not refuse).
            $game->globals->set(Game::CHALLENGE_TYPE, Game::RAGE_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Hulk", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }

        // WHY: Target is chosen after Triggered. abilityId on this event is
        // TRANSITION_INTERNAL_ID, which a technique can overwrite. CHOSEN_ACTION
        // still identifies this instance (two copies must not both arm).
        if ($event instanceof EventChallengeIssued
            && ! $event->canceled
            && $this->thisActionIssuedChallenge($event->theah))
        {
            $this->FirstCombatParryCharacterId = $event->defenderId;
            $this->markOwnerUpdated($event->theah);
        }

        // WHY: Intervening accepts and replaces the defender, and does not fire
        // EventChallengeAccepted. The new adversary is who plays the first combat card.
        if ($event instanceof EventCharacterIntervened
            && $this->FirstCombatParryCharacterId != 0
            && $this->FirstCombatParryCharacterId == $event->oldTargetId
            && $event->theah->game->globals->get(Game::CHALLENGE_TYPE) == Game::RAGE_CHALLENGE_TYPE)
        {
            $this->FirstCombatParryCharacterId = $event->newTargetId;
            $this->markOwnerUpdated($event->theah);
        }

        if ($event instanceof EventDuelCalculateCombatCardStats
            && $this->FirstCombatParryCharacterId != 0
            && $event->actorId == $this->FirstCombatParryCharacterId)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->explanations[] = sprintf(
                "%s: the adversary's first combat card has -2 Parry",
                $owner->getInjectCode()
            );
            $event->removeParry(2);

            // WHY Maneuver column: same reload-safe label as Rattle the Rigging.
            // Combat Card column only stores card ids. Skip when Parry is dashed.
            if (! $event->dashedParry)
            {
                $note = sprintf("%s: -2 Parry to combat card", $owner->Name);
                $event->theah->recordDuelRoundColumnNote('maneuver', 'note_' . $this->Id, $note);
            }

            $this->FirstCombatParryCharacterId = 0;
            $this->markOwnerUpdated($event->theah);
        }

        if ($event instanceof EventDuelEnd && $this->FirstCombatParryCharacterId != 0)
        {
            $this->FirstCombatParryCharacterId = 0;
            $this->markOwnerUpdated($event->theah);
        }

        // WHY: Cancel / fizzle / refuse with no duel. Gate on !IN_DUEL so a resolve
        // during the duel cannot wipe the arm before the adversary's first card.
        // Duel-end ActionResolved runs after IN_DUEL is already false; EventDuelEnd
        // also clears.
        if ($event instanceof EventActionResolved
            && $this->FirstCombatParryCharacterId != 0
            && ! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            $this->FirstCombatParryCharacterId = 0;
            $this->markOwnerUpdated($event->theah);
        }
    }

    private function thisActionIssuedChallenge(Theah $theah): bool
    {
        $game = $theah->game;
        return $game->globals->get(Game::CHALLENGE_TYPE) == Game::RAGE_CHALLENGE_TYPE
            && $this->Id == $game->globals->get(Game::CHOSEN_ACTION);
    }

    private function markOwnerUpdated(Theah $theah): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }
}
