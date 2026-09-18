<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04033 extends Technique
{
    // WHY: Choice happens in Resolve before Calculate (createTechniqueTransitionEvent is HIGHEST_PRIORITY).
    public bool $UseThrust = false;

    // WHY: Public so IsUpdated on the owner persists the deferred NewRound prompt across rounds.
    public bool $PendingThreatChoice = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust or +1 Parry; then may add threat to Iago next adversary round");
        $this->UseThrust = false;
        $this->PendingThreatChoice = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        // WHY: Challenge activation has no duel round actor — only gate actor==owner
        // when IN_DUEL. Outside duel (challenge TechniqueAvailable), performer is
        // already the host via getAvailableCharacterTechniques (04017 / 04021b).
        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            $actor = $theah->getDuelRoundActor();
            if ($actor === null || $actor->Id !== $owner->Id)
            {
                return false;
            }
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null)
            {
                return;
            }

            $this->UseThrust = false;
            $this->PendingThreatChoice = true;
            $owner->IsUpdated = true;

            // WHY createTechniqueTransitionEvent: HIGHEST_PRIORITY so Thrust/Parry choice
            // completes before EventDuelCalculateTechniqueValues (queued after Resolve).
            // Same key "04033" is dispatcher-scoped: RESOLVE_TECHNIQUE_EVENTS → challenge
            // picker; DUEL_CHOOSE_TECHNIQUE_EVENTS → duel picker; DUEL_NEW_ROUND_EVENTS →
            // deferred threat prompt.
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "04033",
                $this->Id
            );
            $event->theah->queueEvent($transition);

            $this->setUsed($event->theah, true);
        }

        // WHY: Challenge has no EventDuelCalculateTechniqueValues — +1 Thrust becomes
        // +1 adversary threat here (PlusOneThrust / 04017). Parry does nothing on
        // challenge; UseThrust is set by the Resolve-time picker before this fires.
        // AdversaryId is already CHOSEN_TARGET from stHighDramaChallengeActionGenerateThreat
        // (post-Intervene final defender) — do not use getDuelRoundOpponent().
        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            if ($this->UseThrust)
            {
                $owner = $this->getOwningCharacter($event->theah);
                if ($owner === null || $owner->Id == $event->actorId)
                {
                    $event->adversaryThreat += 1;
                    $event->explanations[] = sprintf(
                        $event->theah->game->translate("%s: Technique [%s] adds 1 Threat."),
                        $owner !== null ? $owner->getInjectCode() : $this->Name,
                        $this->Name
                    );
                }
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $inject = $owner !== null ? $owner->getInjectCode() : $this->Name;

            if ($this->UseThrust)
            {
                $event->thrust += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Technique [%s] adds +1 Thrust."),
                    $inject,
                    $this->Name
                );
            }
            else
            {
                $event->parry += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Technique [%s] adds +1 Parry."),
                    $inject,
                    $this->Name
                );
            }
        }

        if ($event instanceof EventDuelNewRound && $this->PendingThreatChoice)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null || $owner->ControllerId == 0)
            {
                return;
            }

            // Adversary's round = actor is not Iago.
            if ($event->actorId == $owner->Id)
            {
                return;
            }

            if ($event->theah->game->characterIsInDiscardOrLocker($owner))
            {
                $this->PendingThreatChoice = false;
                $owner->IsUpdated = true;
                return;
            }

            // Clear before queueing so we only prompt once even if NewRound re-fires.
            $this->PendingThreatChoice = false;
            $owner->IsUpdated = true;

            // Same transition key as the Thrust/Parry picker — DUEL_NEW_ROUND_EVENTS
            // routes "04033" to DUEL_NEW_ROUND_04033 (01090 pattern).
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "04033",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearDeferredState($event->theah);
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->clearDeferredState($event->theah);
        }
    }

    private function clearDeferredState(Theah $theah): void
    {
        $this->UseThrust = false;
        $this->PendingThreatChoice = false;
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    /**
     * @return array{0: int, 1: int}|null  [challengerThreat, defenderThreat] or null if Iago is not a participant
     */
    private function threatDeltaForIago(Theah $theah, int $iagoId): ?array
    {
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        if ($challengerId === null || $defenderId === null)
        {
            return null;
        }

        if ($challengerId == $iagoId)
        {
            return [1, 0];
        }

        if ($defenderId == $iagoId)
        {
            return [0, 1];
        }

        return null;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        // WHY: Challenge picker is a separate state so nextState returns to
        // RESOLVE_TECHNIQUE_EVENTS — reusing DUEL_CHOOSE_TECHNIQUE_04033 would
        // jump into the duel event hub mid-challenge (04017 lesson).
        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04033
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04033)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $this->UseThrust = $id == 1;
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }

            if ($this->UseThrust)
            {
                $game->notify->all("message", clienttranslate('${player_name} chooses +1 Thrust for ${owner_inject_code}\'s Technique.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "owner_inject_code" => $owner !== null ? $owner->getInjectCode() : $this->Name,
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${player_name} chooses +1 Parry for ${owner_inject_code}\'s Technique.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "owner_inject_code" => $owner !== null ? $owner->getInjectCode() : $this->Name,
                ]);
            }

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::DUEL_NEW_ROUND_04033)
        {
            $owner = $this->getOwningCharacter($game->theah);

            // id 1 = Add Threat; id 0 = Pass
            if ($id == 1 && $owner !== null)
            {
                $deltas = $this->threatDeltaForIago($game->theah, $owner->Id);
                if ($deltas !== null)
                {
                    $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} adds a threat to Iago.'), [
                        "owner_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getActivePlayerName(),
                    ]);

                    $threatEvent = EventFactory::createThreatModifiedEvent($deltas[0], $deltas[1]);
                    $game->theah->queueEvent($threatEvent);
                }
            }
            else if ($owner !== null)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declines to add a threat to Iago.'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getActivePlayerName(),
                ]);
            }

            $game->gamestate->nextState();
        }
    }
}
