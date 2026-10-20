<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

/** StatesTrait: the challenge pipeline (technique offer, issue, setup, cancel check, threat, resolution). */
class StatesTrait_Challenge_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: challenge pipeline';
    }

    /**
     * Challenger (player 1) and target (player 2) both at the Docks.
     *
     * @return array{0:GenericCharacter,1:GenericCharacter}
     */
    private function duelists(StatesTraitGame $game, ?GenericCharacter $challenger = null): array
    {
        $challenger ??= new GenericCharacter('Challenger');
        $c = $game->place($challenger, Game::LOCATION_CITY_DOCKS, 1);
        $t = $game->place(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
        $game->globals->set(Game::CURRENT_PLAYER, 1);
        $game->globals->set(Game::CHOSEN_PERFORMER, $c->Id);
        $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
        return [$c, $t];
    }

    /** Character that reports an available Technique so stTechniqueAvailable offers the picker. */
    private function techniqueHolder(): GenericCharacter
    {
        return new class('Technician') extends GenericCharacter {
            public function getTechniquesArray(Game $game, bool $mustBeAvailable = false): array
            {
                return [['id' => 'technique-1']];
            }
        };
    }

    public function tests(): array
    {
        return [
            // ---- stTechniqueAvailable ---------------------------------------------

            'stTechniqueAvailable skips the challenge when it was cancelled before the technique step' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_CANCELLED, true);

                $game->stTechniqueAvailable();

                Assert::false($game->globals->has(Game::CHALLENGE_CANCELLED), 'cancel flag consumed');
                $resolved = $game->queued(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
                Assert::same(1, $resolved[0]->playerId, 'performer controller');
                Assert::same([true], $game->theah->runEventsCalls, 'ran without transitions');
                $note = $game->notify->messages[0];
                Assert::same('challengeCancelled', $note['type'], 'notification');
                Assert::same([$c->Id, $t->Id, 1, 2], [
                    $note['args']['challengerId'], $note['args']['defenderId'],
                    $note['args']['challengingPlayerId'], $note['args']['defendingPlayerId'],
                ], 'participants in the notification');
                Assert::same(['challengeSkipped'], $game->gamestate->transitions, 'transition');
                Assert::count(0, $game->queued(EventChallengeIssued::class), 'no challenge issued');
            },

            'stTechniqueAvailable skips safely with no performer, resolving for the current player' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);

                $game->stTechniqueAvailable();

                $resolved = $game->queued(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
                Assert::same(2, $resolved[0]->playerId, 'falls back to CURRENT_PLAYER');
                Assert::same(0, $game->notify->messages[0]['args']['challengingPlayerId'], 'no performer controller');
                Assert::same(['challengeSkipped'], $game->gamestate->transitions, 'transition');
            },

            'stTechniqueAvailable with nobody to resolve for queues no ActionResolved' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);

                $game->stTechniqueAvailable();

                Assert::count(0, $game->queued(EventActionResolved::class), 'nothing to resolve');
                Assert::same([], $game->theah->runEventsCalls, 'no drain');
                Assert::same(['challengeSkipped'], $game->gamestate->transitions, 'transition');
            },

            'stTechniqueAvailable offers the technique picker to the challenger' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game, $this->techniqueHolder());
                $game->globals->set(Game::CURRENT_PLAYER, 2); // stale: a reactor was active

                $game->stTechniqueAvailable();

                Assert::same([1], $game->gamestate->activePlayerChanges, 'challenger controller made active');
                Assert::same(['hasTechique'], $game->gamestate->transitions, 'transition (spelling matches states.inc.php)');
                Assert::count(0, $game->queued(EventChallengeIssued::class), 'challenge is not issued before the technique is chosen');
            },

            'stTechniqueAvailable with no technique issues the challenge and hands over to the target' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);

                $game->stTechniqueAvailable();

                $issued = $game->queued(EventChallengeIssued::class);
                Assert::count(1, $issued, 'challenge issued');
                Assert::same($c->Id, $issued[0]->challengerId, 'challenger');
                Assert::same($t->Id, $issued[0]->defenderId, 'defender');
                Assert::same([2], $game->gamestate->activePlayerChanges, 'target controller decides');
                Assert::same(['noTechnique'], $game->gamestate->transitions, 'transition');
            },

            'stTechniqueAvailable (Andriana) skips when the target is no longer at her location' => function () {
                $game = new StatesTraitGame();
                $andriana = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $andriana->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $andriana->getActions()[0]->Id);

                $game->stTechniqueAvailable();

                Assert::count(1, $game->queued(EventActionResolved::class), 'action resolved');
                Assert::same(['challengeSkipped'], $game->gamestate->transitions, 'transition');
                Assert::contains('no longer at the same location', $game->notify->messages[0]['message'], 'reason logged');
                Assert::count(0, $game->queued(EventChallengeIssued::class), 'no challenge');
            },

            'stTechniqueAvailable (Andriana) skip message works when the target vanished entirely' => function () {
                $game = new StatesTraitGame();
                $andriana = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $andriana->Id);
                $game->globals->set(Game::CHOSEN_TARGET, 424242);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $andriana->getActions()[0]->Id);

                $game->stTechniqueAvailable();

                Assert::contains('the target is no longer at the same location', $game->notify->messages[0]['message'], 'target-less message');
                Assert::same(['challengeSkipped'], $game->gamestate->transitions, 'transition');
            },

            'stTechniqueAvailable (Andriana) still issues the challenge when the target is co-located' => function () {
                $game = new StatesTraitGame();
                $andriana = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $andriana->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $andriana->getActions()[0]->Id);

                $game->stTechniqueAvailable();

                Assert::count(1, $game->queued(EventChallengeIssued::class), 'challenge issued');
                Assert::same(['noTechnique'], $game->gamestate->transitions, 'transition');
            },

            // ---- stIssueChallenge ---------------------------------------------------

            'stIssueChallenge queues ChallengeIssued carrying technique, source and ability ids' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->globals->set(Game::CHOSEN_TECHNIQUE, 'tech-7');
                $game->globals->set(Game::TRANSITION_SOURCE_ID, 55);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, 'ability-9');

                $game->stIssueChallenge();

                $issued = $game->queued(EventChallengeIssued::class);
                Assert::count(1, $issued, 'event');
                Assert::same(1, $issued[0]->playerId, 'player');
                Assert::same('tech-7', $issued[0]->activatedTechniqueId, 'technique');
                Assert::same(55, $issued[0]->sourceId, 'source');
                Assert::same('ability-9', $issued[0]->abilityId, 'ability');
                Assert::contains('is issuing a challenge', $game->notify->messages[0]['message'], 'announced');
            },

            'stIssueChallenge resets the cancelled and accepted flags' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_CANCELLED, true);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stIssueChallenge();

                Assert::false($game->globals->get(Game::CHALLENGE_CANCELLED), 'cancelled reset');
                Assert::false($game->globals->get(Game::CHALLENGE_ACCEPTED), 'accepted reset (stale accept must not outrank a later cancel)');
            },

            'stIssueChallenge engages the challenger for the normal challenge types' => function () {
                foreach ([
                    Game::NORMAL_CHALLENGE_TYPE, Game::SERVO_SCARPA_CHALLENGE_TYPE, Game::TORVO_ESPADA_CHALLENGE_TYPE,
                    Game::AJA_CHALLENGE_TYPE, Game::DANILO_CHALLENGE_TYPE, Game::RAVEN_CHALLENGE_TYPE,
                ] as $type) {
                    $game = new StatesTraitGame();
                    [$c] = $this->duelists($game);
                    $game->globals->set(Game::CHALLENGE_TYPE, $type);

                    $game->stIssueChallenge();

                    $engaged = $game->queued(EventCardEngaged::class);
                    Assert::count(1, $engaged, "engage for challenge type {$type}");
                    Assert::same($c->Id, $engaged[0]->cardId, "challenger engaged for type {$type}");
                }
            },

            'stIssueChallenge does not engage the challenger for attachment / special challenge types' => function () {
                foreach ([
                    Game::TRISKELION_CHALLENGE_TYPE, Game::CAVALIER_HAT_CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE,
                    Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE, Game::VALERI_CHALLENGE_TYPE, Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE,
                ] as $type) {
                    $game = new StatesTraitGame();
                    $this->duelists($game);
                    $game->globals->set(Game::CHALLENGE_TYPE, $type);

                    $game->stIssueChallenge();

                    Assert::count(0, $game->queued(EventCardEngaged::class), "no engage for challenge type {$type}");
                }
            },

            'stIssueChallenge strips an orphaned 05DabneyUS01_2 transition from the queue' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $orphan = new \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition();
                $orphan->transition = '05DabneyUS01_2';
                $keep = new \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition();
                $keep->transition = 'other';
                $game->theah->queueEvent($orphan);
                $game->theah->queueEvent($keep);

                $game->stIssueChallenge();

                $transitions = array_map(
                    fn($e) => $e->transition,
                    $game->queued(\Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition::class)
                );
                Assert::same(['other'], $transitions, 'only the orphan removed');
            },

            // ---- stSetupChallenge ---------------------------------------------------

            'stSetupChallenge records the site and passes the decision to the target controller' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);

                $game->stSetupChallenge();

                Assert::same(Game::LOCATION_CITY_DOCKS, $game->globals->get(Game::CHOSEN_LOCATION), 'challenge site');
                $change = $game->queued(EventChangeActivePlayer::class);
                Assert::count(1, $change, 'change player');
                Assert::same(2, $change[0]->playerId, 'target controller');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'normal challenge pays nothing here');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stSetupChallenge (Unsanctioned Duel) engages the challenger using the source card' => function () {
                $game = new StatesTraitGame();
                [$c] = $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, 321);

                $game->stSetupChallenge();

                $engaged = $game->queued(EventCardEngaged::class);
                Assert::count(1, $engaged, 'engage');
                Assert::same([1, $c->Id, 321], [$engaged[0]->playerId, $engaged[0]->cardId, $engaged[0]->sourceId], 'player / card / source');
            },

            'stSetupChallenge (Triskelion / Cavalier Hat) engages the action owner on behalf of the equipped character' => function () {
                foreach ([Game::TRISKELION_CHALLENGE_TYPE, Game::CAVALIER_HAT_CHALLENGE_TYPE] as $type) {
                    $game = new StatesTraitGame();
                    $owner = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                    $t = $game->place(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                    $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
                    $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                    $game->globals->set(Game::CHALLENGE_TYPE, $type);
                    // Stand-in for an attachment action: any action with an owning card + character.
                    $game->globals->set(Game::CHOSEN_ACTION, $owner->getActions()[0]->Id);

                    $game->stSetupChallenge();

                    $engaged = $game->queued(EventCardEngaged::class);
                    Assert::count(1, $engaged, "engage for type {$type}");
                    Assert::same($owner->Id, $engaged[0]->cardId, "equipped character engaged for type {$type}");
                    Assert::same($owner->Id, $engaged[0]->sourceId, "owning card is the source for type {$type}");
                }
            },

            // ---- stChallengeActionCheckCancelled ----------------------------------

            'stChallengeActionCheckCancelled: an untouched challenge goes to the target controller' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);

                $game->stChallengeActionCheckCancelled();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'target controller');
                Assert::same(['notCancelled'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled: an accepted challenge proceeds' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stChallengeActionCheckCancelled();

                Assert::same(['accepted'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled: a cancel flag cleans up markers and wins over a stale accept' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $c->addCondition(Game::DUEL_CHALLENGER);
                $t->addCondition(Game::DUEL_DEFENDER);
                $game->globals->set(Game::CHALLENGE_CANCELLED, true);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $game->globals->set(Game::PASS_COUNT, 2);

                $game->stChallengeActionCheckCancelled();

                Assert::false($c->hasCondition(Game::DUEL_CHALLENGER), 'challenger marker removed');
                Assert::false($t->hasCondition(Game::DUEL_DEFENDER), 'defender marker removed');
                Assert::same([$c->Id, $t->Id], array_slice($game->updatedCardObjectIds, 0, 2), 'both persisted');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::false($game->globals->has(Game::CHALLENGE_ACCEPTED), 'accept cleared');
                $resolved = $game->queued(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
                Assert::same(1, $resolved[0]->playerId, 'for the challenger controller');
                Assert::same(1, $game->clearLastKnownCalls, 'last-known participants cleared');
                Assert::same('challengeCancelled', $game->notify->messages[0]['type'], 'notification');
                Assert::same(['cancelled'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled: an uncontrolled target auto-cancels the challenge' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $t->ControllerId = 0;

                $game->stChallengeActionCheckCancelled();

                Assert::same(['cancelled'], $game->gamestate->transitions, 'transition');
            },

            // Regression: a target destroyed before this gate (Stiletto) is no longer in the city, so
            // $target was null and the trait dereferenced it (warning, then changeActivePlayer(null)).
            'stChallengeActionCheckCancelled: a destroyed target hands control to its last-known controller' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $snapshot = clone $t;
                $game->theah->removeCardFromWorld($t);
                $game->lastKnownCharacters[$t->Id] = $snapshot;

                $game->stChallengeActionCheckCancelled();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'defending player is still the one asked');
                Assert::same(['notCancelled'], $game->gamestate->transitions, 'Accept UI is kept (Stiletto ruling)');
            },

            // Theah::getCardById reads cards outside the city from the DB, so a destroyed target still resolves.
            'stChallengeActionCheckCancelled: a destroyed target without a snapshot is still found via its DB row' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->theah->removeCardFromWorld($t); // still in $game->dbCards

                $game->stChallengeActionCheckCancelled();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'controller from the stored card');
                Assert::same(['notCancelled'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled: a destroyed target on a Move Along challenge still offers the choice to its controller' => function () {
                $game = new StatesTraitGame();
                $owner = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $t = $game->place(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE);
                $game->theah->removeCardFromWorld($t);

                $game->stChallengeActionCheckCancelled();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'controller from the stored card');
                Assert::same(['moveAlongChoice'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled: a target whose controller cannot be found cancels instead of stalling' => function () {
                $game = new StatesTraitGame();
                [$c] = $this->duelists($game);
                $game->globals->set(Game::CHOSEN_TARGET, 987654); // not in the city, no snapshot, no DB row

                $game->stChallengeActionCheckCancelled();

                Assert::same([], $game->gamestate->activePlayerChanges, 'never changes to a null player');
                Assert::same(['cancelled'], $game->gamestate->transitions, 'cancelled');
                $resolved = $game->queued(EventActionResolved::class);
                Assert::same(1, $resolved[0]->playerId, 'challenger controller resolves the action');
            },

            'stChallengeActionCheckCancelled: an accepted challenge does not need the target to still exist' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->theah->removeCardFromWorld($t);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stChallengeActionCheckCancelled();

                Assert::same(['accepted'], $game->gamestate->transitions, 'accepted');
            },

            'stChallengeActionCheckCancelled (Move Along) restores the transition ids and offers the choice' => function () {
                $game = new StatesTraitGame();
                $owner = $game->place(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $t = $game->place(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE);
                $actionId = $owner->getActions()[0]->Id;
                $game->globals->set(Game::CHOSEN_ACTION, $actionId);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, 'clobbered-by-technique');

                $game->stChallengeActionCheckCancelled();

                Assert::same($owner->Id, $game->globals->get(Game::TRANSITION_SOURCE_ID), 'source restored');
                Assert::same($actionId, $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'ability restored');
                Assert::same([2], $game->gamestate->activePlayerChanges, 'target controller chooses');
                Assert::same(['moveAlongChoice'], $game->gamestate->transitions, 'transition');
            },

            'stChallengeActionCheckCancelled (Move Along) after Accept goes to accepted, not the choice' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::MOVE_ALONG_CHALLENGE_TYPE);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stChallengeActionCheckCancelled();

                Assert::same(['accepted'], $game->gamestate->transitions, 'accepted wins so the gate does not loop');
            },

            'stChallengeActionCheckCancelled: Maryam Benu Pleroma cancels a Defending Honor challenge once' => function () {
                $game = new StatesTraitGame();
                $maryam = $game->place(new _01186(), Game::LOCATION_CITY_DOCKS, 1);
                $t = $game->place(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $honor = $game->place(new _01078(), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $maryam->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $honor->Id);

                $game->stChallengeActionCheckCancelled();

                Assert::true($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'ability marked used');
                Assert::same(['cancelled'], $game->gamestate->transitions, 'cancelled');
                Assert::true($game->globals->get(Game::CHALLENGE_CANCELLED), 'cancel flag set');

                // Second time: already used, so it must not cancel again.
                $game->gamestate->transitions = [];
                $game->globals->delete(Game::CHALLENGE_CANCELLED);
                $game->stChallengeActionCheckCancelled();
                Assert::same(['notCancelled'], $game->gamestate->transitions, 'only once');
            },

            // ---- technique / threat ------------------------------------------------

            'stHighDramaChallengeActionResolveTechnique queues ResolveTechnique for a chosen technique' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->globals->set(Game::CHOSEN_TECHNIQUE, 'tech-3');

                $game->stHighDramaChallengeActionResolveTechnique();

                $events = $game->queued(EventResolveTechnique::class);
                Assert::count(1, $events, 'event');
                Assert::same([1, $c->Id, $t->Id, 'tech-3', false], [
                    $events[0]->playerId, $events[0]->actorId, $events[0]->adversaryId,
                    $events[0]->techniqueId, $events[0]->inDuel,
                ], 'event fields (not in a duel yet)');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stHighDramaChallengeActionResolveTechnique does nothing without a technique' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);

                $game->stHighDramaChallengeActionResolveTechnique();

                Assert::count(0, $game->queued(EventResolveTechnique::class), 'no event');
                Assert::same([null], $game->gamestate->transitions, 'still advances');
            },

            'stHighDramaChallengeActionGenerateThreat queues GenerateChallengeThreat with the challenge stat' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);
                $game->globals->set(Game::CHOSEN_TECHNIQUE, 'tech-4');

                $game->stHighDramaChallengeActionGenerateThreat();

                $events = $game->queued(EventGenerateChallengeThreat::class);
                Assert::count(1, $events, 'event');
                Assert::same([$c->Id, $t->Id, 'tech-4', Game::STAT_FINESSE], [
                    $events[0]->actorId, $events[0]->adversaryId, $events[0]->techniqueId, $events[0]->statUsed,
                ], 'event fields');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stHighDramaChallengeActionGenerateThreat defaults the technique id to empty' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionGenerateThreat();

                Assert::same('', $game->queued(EventGenerateChallengeThreat::class)[0]->techniqueId, 'empty technique');
            },

            // ---- resolution --------------------------------------------------------

            'stHighDramaChallengeActionResolution: an accepted challenge starts the duel' => function () {
                $game = new StatesTraitGame();
                $this->duelists($game);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $game->globals->set(Game::PASS_COUNT, 3);

                $game->stHighDramaChallengeActionResolution();

                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['accepted'], $game->gamestate->transitions, 'transition');
                Assert::count(0, $game->queued(EventCharacterBeingWounded::class), 'no wounds');
            },

            'stHighDramaChallengeActionResolution: an accepted challenge whose target left play fizzles' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->discardOrLockerByCardId[$t->Id] = true;
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stHighDramaChallengeActionResolution();

                Assert::same(['fizzled'], $game->gamestate->transitions, 'transition');
                Assert::false($game->globals->has(Game::CHALLENGE_ACCEPTED), 'accept cleared so it cannot leak');
                Assert::same([['challengerId' => $c->Id, 'defenderId' => $t->Id]], $game->clearMarkerCalls, 'markers cleared');
                Assert::same(1, $game->clearLastKnownCalls, 'last-known cleared');
                Assert::count(1, $game->queued(EventActionResolved::class), 'action resolved');
                Assert::contains('Threat fizzles', $game->notify->messages[0]['message'], 'logged');
            },

            'stHighDramaChallengeActionResolution: an accepted challenge with a vanished target fizzles for the current player' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, 424241);
                $game->globals->set(Game::CHOSEN_TARGET, 424242);
                $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $game->stHighDramaChallengeActionResolution();

                Assert::same(['fizzled'], $game->gamestate->transitions, 'transition');
                Assert::same(2, $game->queued(EventActionResolved::class)[0]->playerId, 'resolved for CURRENT_PLAYER');
            },

            // NOTE on direction: a participant's threat is the threat THEY TAKE (generated by the other
            // side). CHALLENGER_THREAT therefore wounds the challenger, with the target as the source and
            // the cap taken from the target's stat; DEFENDER_THREAT wounds the target.
            'stHighDramaChallengeActionResolution: a refused challenge wounds the challenger by CHALLENGER_THREAT' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $t->ModifiedCombat = 5;
                $game->globals->set(Game::CHALLENGER_THREAT, 2);
                $game->globals->set(Game::DEFENDER_THREAT, 0);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionResolution();

                $wounds = $game->queued(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound event');
                Assert::same([$c->Id, $t->Id, 2], [$wounds[0]->characterId, $wounds[0]->sourceId, $wounds[0]->wounds], 'challenger wounded, target is the source');
                Assert::count(1, $game->queued(EventActionResolved::class), 'action resolved');
                Assert::same(1, $game->clearLastKnownCalls, 'last-known cleared');
                Assert::same(['rejected'], $game->gamestate->transitions, 'transition');
            },

            'stHighDramaChallengeActionResolution: Restricted Hostilities caps challenger threat at the target stat used' => function () {
                foreach ([
                    [Game::STAT_COMBAT, 'ModifiedCombat'],
                    [Game::STAT_FINESSE, 'ModifiedFinesse'],
                    [Game::STAT_INFLUENCE, 'ModifiedInfluence'],
                ] as [$stat, $property]) {
                    $game = new StatesTraitGame();
                    [$c, $t] = $this->duelists($game);
                    $t->ModifiedCombat = 9;
                    $t->ModifiedFinesse = 9;
                    $t->ModifiedInfluence = 9;
                    $t->$property = 2;
                    $game->globals->set(Game::CHALLENGER_THREAT, 6);
                    $game->globals->set(Game::DEFENDER_THREAT, 0);
                    $game->globals->set(Game::CHALLENGE_STAT, $stat);

                    $game->stHighDramaChallengeActionResolution();

                    $wounds = $game->queued(EventCharacterBeingWounded::class);
                    Assert::same(2, $wounds[0]->wounds, "capped by target {$stat}");
                    Assert::contains('Restricted Hostilities', $wounds[0]->reason, "reason explains the cap for {$stat}");
                }
            },

            'stHighDramaChallengeActionResolution: a refused challenge wounds the target by DEFENDER_THREAT' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $c->ModifiedCombat = 5;
                $game->globals->set(Game::CHALLENGER_THREAT, 0);
                $game->globals->set(Game::DEFENDER_THREAT, 3);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionResolution();

                $wounds = $game->queued(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound event');
                Assert::same([$t->Id, $c->Id, 3], [$wounds[0]->characterId, $wounds[0]->sourceId, $wounds[0]->wounds], 'target wounded, challenger is the source');
            },

            'stHighDramaChallengeActionResolution: defender threat is capped by the challenger stat unless lethal' => function () {
                $game = new StatesTraitGame();
                [$c] = $this->duelists($game);
                $c->ModifiedCombat = 2;
                $game->globals->set(Game::CHALLENGER_THREAT, 0);
                $game->globals->set(Game::DEFENDER_THREAT, 5);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $game->stHighDramaChallengeActionResolution();
                Assert::same(2, $game->queued(EventCharacterBeingWounded::class)[0]->wounds, 'capped');

                $game = new StatesTraitGame();
                [$c] = $this->duelists($game);
                $c->ModifiedCombat = 2;
                $game->globals->set(Game::CHALLENGER_THREAT, 0);
                $game->globals->set(Game::DEFENDER_THREAT, 5);
                $game->globals->set(Game::DEFENDER_THREAT_IS_LETHAL, true);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $game->stHighDramaChallengeActionResolution();
                Assert::same(5, $game->queued(EventCharacterBeingWounded::class)[0]->wounds, 'lethal threat ignores the cap');
            },

            'stHighDramaChallengeActionResolution: both threats wound both participants' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $c->ModifiedCombat = 5;
                $t->ModifiedCombat = 5;
                $game->globals->set(Game::CHALLENGER_THREAT, 1);
                $game->globals->set(Game::DEFENDER_THREAT, 2);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionResolution();

                $wounds = $game->queued(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'both wound events');
                Assert::same([$c->Id, 1], [$wounds[0]->characterId, $wounds[0]->wounds], 'challenger wounded first (CHALLENGER_THREAT)');
                Assert::same([$t->Id, 2], [$wounds[1]->characterId, $wounds[1]->wounds], 'target wounded second (DEFENDER_THREAT)');
            },

            'stHighDramaChallengeActionResolution: a refused challenge against a departed target only fizzles' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $game->discardOrLockerByCardId[$t->Id] = true;
                $game->globals->set(Game::CHALLENGER_THREAT, 3);
                $game->globals->set(Game::DEFENDER_THREAT, 3);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionResolution();

                Assert::count(0, $game->queued(EventCharacterBeingWounded::class), 'no wounds');
                Assert::contains('Threat fizzles', $game->notify->messages[0]['message'], 'logged');
                Assert::count(1, $game->queued(EventActionResolved::class), 'action still resolves');
                Assert::same(['rejected'], $game->gamestate->transitions, 'transition');
            },

            'stHighDramaChallengeActionResolution: a dead challenger takes no wound, and the target is capped by the challenger last-known stat' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duelists($game);
                $c->Location = 'Discard-1';
                $game->discardOrLockerByCardId[$c->Id] = true;
                $lastKnown = new GenericCharacter('Challenger (last known)');
                $lastKnown->ModifiedCombat = 2;
                $game->lastKnownCharacters[$c->Id] = $lastKnown;
                $game->globals->set(Game::CHALLENGER_THREAT, 4);
                $game->globals->set(Game::DEFENDER_THREAT, 4);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $game->stHighDramaChallengeActionResolution();

                $wounds = $game->queued(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'only the defender-threat wound (a dead challenger cannot be wounded again)');
                Assert::same([$t->Id, $c->Id], [$wounds[0]->characterId, $wounds[0]->sourceId], 'target wounded, source is the dead challenger id');
                Assert::same(2, $wounds[0]->wounds, 'capped by the last-known Combat');
            },
        ];
    }
}
