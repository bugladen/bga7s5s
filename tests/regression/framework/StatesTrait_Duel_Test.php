<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01018;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGambleSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** StatesTrait: duel start, rounds, combat cards, maneuvers and gambling. */
class StatesTrait_Duel_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: duel setup and rounds';
    }

    /**
     * Challenger (player 1) and defender (player 2) at the Docks with a seeded duel row.
     *
     * @return array{0:GenericCharacter,1:GenericCharacter}
     */
    private function duel(StatesTraitGame $game, int $challengerThreat = 2, int $defenderThreat = 3): array
    {
        $c = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $t = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $game->globals->set(Game::DUEL_ID, 5);
        $game->duelRow = [
            'challenging_player_id' => 1,
            'challenger_id' => $c->Id,
            'challenger_threat' => $challengerThreat,
            'defending_player_id' => 2,
            'defender_id' => $t->Id,
            'defender_threat' => $defenderThreat,
        ];
        return [$c, $t];
    }

    public function tests(): array
    {
        return [
            // ---- stDuelStarted -------------------------------------------------------

            'stDuelStarted numbers the duel after the highest existing id and stores it' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->globals->delete(Game::DUEL_ID);
                $game->duelMaxId = 4;
                $game->globals->set(Game::CHOSEN_PERFORMER, $c->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                $game->globals->set(Game::CHALLENGER_THREAT, 2);
                $game->globals->set(Game::DEFENDER_THREAT, 3);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

                $game->stDuelStarted();

                Assert::same(5, $game->globals->get(Game::DUEL_ID), 'duel id');
                Assert::true($game->globals->get(Game::IN_DUEL), 'in duel');
                Assert::same(Game::NORMAL_DUEL_TYPE, $game->globals->get(Game::DUEL_TYPE), 'normal duel type');
                Assert::count(1, $game->dbQueries, 'one INSERT');
                Assert::contains("VALUES (5, 1, {$c->Id}, 2, {$t->Id}, 2, 3)", preg_replace('/\s+/', ' ', $game->dbQueries[0]), 'duel row');

                $note = $game->notify->messages[0];
                Assert::same('duelStarted', $note['type'], 'notification');
                Assert::same(
                    [5, $c->Id, 1, $t->Id, 2, 2, 3, Game::STAT_FINESSE],
                    [
                        $note['args']['duelId'], $note['args']['challengerId'], $note['args']['challengingPlayerId'],
                        $note['args']['defenderId'], $note['args']['defendingPlayerId'],
                        $note['args']['challengerThreat'], $note['args']['defenderThreat'], $note['args']['challengeStat'],
                    ],
                    'notification args'
                );
                $events = $game->queued(EventDuelStarted::class);
                Assert::count(1, $events, 'DuelStarted');
                Assert::same([$c->Id, $t->Id], [$events[0]->challengerId, $events[0]->defenderId], 'participants');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuelStarted resets a stale Vladislav duel type and starts at duel id 1' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->globals->delete(Game::DUEL_ID);
                $game->globals->set(Game::DUEL_TYPE, Game::VLADISLAV_DUEL_TYPE);
                $game->globals->set(Game::CHOSEN_PERFORMER, $c->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $t->Id);
                $game->globals->set(Game::CHALLENGER_THREAT, 0);
                $game->globals->set(Game::DEFENDER_THREAT, 0);

                $game->stDuelStarted();

                Assert::same(1, $game->globals->get(Game::DUEL_ID), 'first duel');
                Assert::same(Game::NORMAL_DUEL_TYPE, $game->globals->get(Game::DUEL_TYPE), 'duel type reset');
            },

            // ---- stDuelNewRound ------------------------------------------------------

            'stDuelNewRound round 1: the defender acts first and becomes the active player' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game, 2, 3);

                $game->stDuelNewRound();

                Assert::same(1, $game->globals->get(Game::DUEL_ROUND), 'round 1');
                Assert::same(2, $game->globals->get(Game::DUEL_CURRENT_PLAYER), 'defender controller current');
                $change = $game->queued(EventChangeActivePlayer::class);
                Assert::count(1, $change, 'change active player');
                Assert::same(2, $change[0]->playerId, 'to the defender');

                $round = $game->queued(EventDuelNewRound::class);
                Assert::count(1, $round, 'DuelNewRound');
                Assert::same(
                    [5, 1, 2, $t->Id, $c->Id, $t->Id, 2, 3, 0, 0, 3],
                    [
                        $round[0]->duelId, $round[0]->round, $round[0]->playerId, $round[0]->actorId,
                        $round[0]->challengerId, $round[0]->defenderId, $round[0]->challengerThreat, $round[0]->defenderThreat,
                        $round[0]->challengerThreatIsLethal, $round[0]->defenderThreatIsLethal, $round[0]->wounds,
                    ],
                    'event fields (wounds = defender threat on round 1)'
                );
                Assert::count(1, $game->dbQueries, 'duel_round INSERT');
                Assert::contains('INSERT INTO duel_round', $game->dbQueries[0], 'row written');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuelNewRound round 1: a lethal defender threat is flagged' => function () {
                $game = new StatesTraitGame();
                $this->duel($game);
                $game->globals->set(Game::DEFENDER_THREAT_IS_LETHAL, true);

                $game->stDuelNewRound();

                $round = $game->queued(EventDuelNewRound::class)[0];
                Assert::same(1, $round->defenderThreatIsLethal, 'defender lethal');
                Assert::same(0, $round->challengerThreatIsLethal, 'challenger never lethal on round 1');
            },

            'stDuelNewRound: pending carry-over threat is added to the pools and to the actor wounds, then cleared' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game, 2, 3);
                $game->globals->set(Game::PENDING_CHALLENGER_THREAT, 1);
                $game->globals->set(Game::PENDING_DEFENDER_THREAT, 2);

                $game->stDuelNewRound();

                $round = $game->queued(EventDuelNewRound::class)[0];
                Assert::same(3, $round->challengerThreat, 'challenger pool 2 + 1');
                Assert::same(5, $round->defenderThreat, 'defender pool 3 + 2');
                Assert::same(5, $round->wounds, 'round-1 actor is the defender, so only the defender carry-over adds');
                Assert::false($game->globals->has(Game::PENDING_CHALLENGER_THREAT), 'pending challenger cleared');
                Assert::false($game->globals->has(Game::PENDING_DEFENDER_THREAT), 'pending defender cleared');
            },

            'stDuelNewRound later rounds: the actors alternate, carrying the previous round ending threat and lethality' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->duelRoundRow = [
                    'actor_id' => $t->Id, // the defender acted last round
                    'ending_challenger_threat' => 4,
                    'ending_defender_threat' => 1,
                    'challenger_threat_is_lethal' => 1,
                    'defender_threat_is_lethal' => 0,
                ];

                $game->stDuelNewRound();

                Assert::same(2, $game->globals->get(Game::DUEL_ROUND), 'round 2');
                $round = $game->queued(EventDuelNewRound::class)[0];
                Assert::same([2, 1, $c->Id], [$round->round, $round->playerId, $round->actorId], 'challenger acts');
                Assert::same([4, 1, 1, 0, 4], [
                    $round->challengerThreat, $round->defenderThreat,
                    $round->challengerThreatIsLethal, $round->defenderThreatIsLethal, $round->wounds,
                ], 'pools and wounds come from the previous round, wounds = challenger pool');
                Assert::count(0, $game->queued(EventChangeActivePlayer::class), 'player switch is handled by stDuelNextPlayer');
            },

            'stDuelNewRound later rounds: after the challenger acted the defender acts' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->globals->set(Game::DUEL_ROUND, 2);
                $game->duelRoundRow = [
                    'actor_id' => $c->Id,
                    'ending_challenger_threat' => 0,
                    'ending_defender_threat' => 6,
                    'challenger_threat_is_lethal' => 0,
                    'defender_threat_is_lethal' => 1,
                ];

                $game->stDuelNewRound();

                $round = $game->queued(EventDuelNewRound::class)[0];
                Assert::same([3, 2, $t->Id, 6, 1], [$round->round, $round->playerId, $round->actorId, $round->wounds, $round->defenderThreatIsLethal], 'defender acts with their pool');
            },

            'stDuelNewRound clears the per-round maneuver and combat-card allowances' => function () {
                $game = new StatesTraitGame();
                $this->duel($game);
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, 9);
                $game->globals->set(Game::DUEL_MANUEVER_ID, 'm');
                $game->globals->set(Game::NEXT_COMBAT_CARD, 9);

                $game->stDuelNewRound();

                Assert::false($game->globals->has(Game::DUEL_PENDING_MANEUVER_CARD), 'pending maneuver card');
                Assert::false($game->globals->has(Game::DUEL_MANUEVER_ID), 'maneuver id');
                Assert::false($game->globals->has(Game::NEXT_COMBAT_CARD), 'next combat card');
            },

            // ---- small duel helpers ----------------------------------------------------

            'stResetDuelAction clears the abnormal-flow flag' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::ABNORMAL_FLOW, true);
                $game->stResetDuelAction();
                Assert::false($game->globals->has(Game::ABNORMAL_FLOW), 'cleared');
            },

            'stDuelGetManeuverFromCombatCardCost stores the card cost and enters the pay state' => function () {
                $game = new StatesTraitGame();
                $card = $game->place(new _01018(), Game::LOCATION_DUELING_LINE, 2);
                $card->WealthCost = 3;
                $game->activePlayerId = 2;
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, $card->Id);

                $game->stDuelGetManeuverFromCombatCardCost();

                Assert::same(3, $game->globals->get(Game::CHOSEN_CARD_COST), 'cost');
                $events = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $events, 'event');
                Assert::same(
                    [2, $card->Id, Game::PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD],
                    [$events[0]->playerId, $events[0]->cardId, $events[0]->payStateType],
                    'pay state for the active player'
                );
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            // ---- stApplyCombatCardStats ---------------------------------------------------

            'stApplyCombatCardStats records the combat card and queues its stats' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $card = $game->place(new _01018(), Game::LOCATION_DUELING_LINE, 1); // Parry 2, Riposte/Thrust dashed
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->globals->set(Game::CHOSEN_CARD, $card->Id);
                $game->duelRoundRow = ['actor_id' => $c->Id];
                $game->theah->duelOpponent = $t;

                $game->stApplyCombatCardStats();

                Assert::count(1, $game->dbQueries, 'one INSERT');
                Assert::contains("INSERT INTO duel_round_combat_card (duel_id, round, combat_card_id) VALUES (5, 1, {$card->Id})", $game->dbQueries[0], 'combat card row');
                $events = $game->queued(EventDuelCalculateCombatCardStats::class);
                Assert::count(1, $events, 'stats event');
                $e = $events[0];
                Assert::same([$c->Id, $t->Id, $card->Id], [$e->actorId, $e->adversaryId, $e->combatCardId], 'actor / adversary / card');
                Assert::same([0, 2, 0], [$e->riposte, $e->parry, $e->thrust], 'printed values');
                Assert::same([true, false, true], [$e->dashedRiposte, $e->dashedParry, $e->dashedThrust], 'dashes copied');
                Assert::false($e->gambled, 'not gambled');
                Assert::false($e->statsAddedToExistingCombatCard, 'normal combat card');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stApplyCombatCardStats marks the stats as gambled from the duel_round flag or the free-gamble global' => function () {
                foreach (['db flag' => [1, false], 'global flag' => [null, true]] as $label => [$dbValue, $global]) {
                    $game = new StatesTraitGame();
                    [$c, $t] = $this->duel($game);
                    $card = $game->place(new _01018(), Game::LOCATION_DUELING_LINE, 1);
                    $game->globals->set(Game::DUEL_ROUND, 1);
                    $game->globals->set(Game::CHOSEN_CARD, $card->Id);
                    $game->globals->set(Game::DUEL_GAMBLED, $global);
                    $game->duelRoundRow = ['actor_id' => $c->Id, 'gambled' => $dbValue];
                    $game->theah->duelOpponent = $t;

                    $game->stApplyCombatCardStats();

                    Assert::true($game->queued(EventDuelCalculateCombatCardStats::class)[0]->gambled, "gambled via {$label}");
                }
            },

            'stApplyCombatCardStats adds Roll the Bones gamble values to the existing 01114 card' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $gambled = $game->place(new _01018(), Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->globals->set(Game::CHOSEN_CARD, $gambled->Id);
                $game->globals->set(Game::ROLL_THE_BONES_CARD_ID, 7777);
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $game->globals->set(Game::DUEL_GAMBLED, true);
                $game->duelRoundRow = ['actor_id' => $c->Id, 'gambled' => 1];
                $game->theah->duelOpponent = $t;

                $game->stApplyCombatCardStats();

                Assert::same([], $game->dbQueries, 'no second combat-card row for the gambled card');
                $e = $game->queued(EventDuelCalculateCombatCardStats::class)[0];
                Assert::same(7777, $e->combatCardId, 'stats attributed to Roll the Bones');
                Assert::true($e->statsAddedToExistingCombatCard, 'additive');
                Assert::false($e->gambled, 'never double-count the gambled bonus');
                Assert::false($game->globals->has(Game::ROLL_THE_BONES_CARD_ID), 'Roll the Bones card id consumed');
                Assert::same(Game::GAMBLE_TYPE_NORMAL, $game->globals->get(Game::GAMBLE_TYPE), 'gamble type reset');
            },

            'stApplyCombatCardStats treats the Roll the Bones card itself as a normal combat card' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $rtb = $game->place(new _01018(), Game::LOCATION_DUELING_LINE, 1);
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->globals->set(Game::CHOSEN_CARD, $rtb->Id);
                $game->globals->set(Game::ROLL_THE_BONES_CARD_ID, $rtb->Id);
                $game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $game->duelRoundRow = ['actor_id' => $c->Id];
                $game->theah->duelOpponent = $t;

                $game->stApplyCombatCardStats();

                Assert::count(1, $game->dbQueries, 'combat card row written');
                Assert::false($game->queued(EventDuelCalculateCombatCardStats::class)[0]->statsAddedToExistingCombatCard, 'not additive');
                Assert::true($game->globals->has(Game::ROLL_THE_BONES_CARD_ID), 'Roll the Bones id kept');
            },

            // ---- stResolveManeuverFromCombatCard --------------------------------------------

            'stResolveManeuverFromCombatCard activates, resolves and prices the maneuver for the pending card' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->activePlayerId = 1;
                $game->theah->duelActor = $c;
                $game->theah->duelOpponent = $t;
                $game->globals->set(Game::DUEL_MANUEVER_ID, 'maneuver-1');
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, 321);
                $game->globals->set(Game::CHOSEN_CARD, 999);

                $game->stResolveManeuverFromCombatCard();

                $activated = $game->queued(EventManeuverActivated::class);
                Assert::same([1, 321, 'maneuver-1'], [$activated[0]->playerId, $activated[0]->ownerId, $activated[0]->maneuverId], 'activated for the pending card, not CHOSEN_CARD');
                $resolve = $game->queued(EventResolveManeuver::class);
                Assert::same([1, $t->Id, 'maneuver-1'], [$resolve[0]->playerId, $resolve[0]->adversaryId, $resolve[0]->maneuverId], 'resolve');
                $values = $game->queued(EventDuelCalculateManeuverValues::class);
                Assert::same([$c->Id, $t->Id, 'maneuver-1'], [$values[0]->actorId, $values[0]->adversaryId, $values[0]->maneuverId], 'values');
                Assert::same(
                    [EventManeuverActivated::class, EventResolveManeuver::class, EventDuelCalculateManeuverValues::class],
                    array_map(fn($e) => $e::class, $game->theah->queuedEvents),
                    'activate, then resolve, then calculate'
                );
                Assert::false($game->globals->has(Game::DUEL_MANUEVER_ID), 'maneuver id consumed');
                Assert::false($game->globals->has(Game::DUEL_PENDING_MANEUVER_CARD), 'pending card consumed');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stResolveManeuverFromCombatCard falls back to CHOSEN_CARD when no card is pending' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->theah->duelActor = $c;
                $game->theah->duelOpponent = $t;
                $game->globals->set(Game::DUEL_MANUEVER_ID, 'maneuver-2');
                $game->globals->set(Game::CHOSEN_CARD, 999);

                $game->stResolveManeuverFromCombatCard();

                Assert::same(999, $game->queued(EventManeuverActivated::class)[0]->ownerId, 'owner is CHOSEN_CARD');
            },

            // Regression: nextState() used to be inside the "has a maneuver" branch, so a game state
            // with no maneuver queued would never leave stResolveManeuverFromCombatCard.
            'stResolveManeuverFromCombatCard still advances (and queues nothing) when no maneuver was chosen' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $game->theah->duelActor = $c;
                $game->theah->duelOpponent = $t;
                $game->globals->set(Game::DUEL_PENDING_MANEUVER_CARD, 321);

                $game->stResolveManeuverFromCombatCard();

                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
                Assert::count(0, $game->theah->queuedEvents, 'no events queued');
                Assert::true($game->globals->has(Game::DUEL_PENDING_MANEUVER_CARD), 'untouched without a maneuver');
            },

            // ---- stSetNextCombatCard -------------------------------------------------------

            'stSetNextCombatCard plays a queued extra combat card (Broken Time) straight into the Dueling Line' => function () {
                $game = new StatesTraitGame();
                $card = $game->place(new _01018(), 'Faction Deck', 1);
                $game->globals->set(Game::NEXT_COMBAT_CARD, $card->Id);

                $game->stSetNextCombatCard();

                Assert::false($game->globals->has(Game::NEXT_COMBAT_CARD), 'request consumed');
                Assert::same($card->Id, $game->globals->get(Game::CHOSEN_CARD), 'chosen card');
                Assert::same(Game::LOCATION_DUELING_LINE, $card->Location, 'moved into the dueling line');
                Assert::same($card->Id, $game->globals->get(Game::DUEL_PENDING_MANEUVER_CARD), 'maneuver option offered for the new card');
                $announce = $game->queued(EventCombatCardAnnounced::class);
                Assert::same([1, $card->Id], [$announce[0]->playerId, $announce[0]->cardId], 'announced for reactions');
                $transition = $game->queued(EventTransition::class);
                Assert::same(['applyCombatCardStats', $card->Id], [$transition[0]->transition, $transition[0]->sourceId], 'stats transition queued');
                Assert::same(['announceCombatCard'], $game->gamestate->transitions, 'transition');
            },

            'stSetNextCombatCard with Roll the Bones armed runs the gamble check once' => function () {
                $game = new StatesTraitGame();
                $actor = $game->place(new GenericCharacter('Gambler'), Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->duelActor = $actor;
                $game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);

                $game->stSetNextCombatCard();

                Assert::false($game->globals->has(Game::ROLL_THE_BONES_ACTIVATED), 'flag consumed');
                Assert::same(['rollTheBones'], $game->gamestate->transitions, 'transition');
            },

            'stSetNextCombatCard prefers a queued extra card over Roll the Bones' => function () {
                $game = new StatesTraitGame();
                $card = $game->place(new _01018(), 'Faction Deck', 1);
                $game->globals->set(Game::NEXT_COMBAT_CARD, $card->Id);
                $game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);

                $game->stSetNextCombatCard();

                Assert::same(['announceCombatCard'], $game->gamestate->transitions, 'extra card wins');
                Assert::true($game->globals->get(Game::ROLL_THE_BONES_ACTIVATED), 'Roll the Bones stays armed for the next pass');
            },

            'stSetNextCombatCard with nothing pending finishes the combat cards' => function () {
                $game = new StatesTraitGame();
                $game->stSetNextCombatCard();
                Assert::same(['noMoreCombatCards'], $game->gamestate->transitions, 'transition');
            },

            // ---- gambling ----------------------------------------------------------------

            'stDuelGambleSetup queues GambleSetup for the round actor and processes events' => function () {
                $game = new StatesTraitGame();
                $actor = $game->place(new GenericCharacter('Gambler'), Game::LOCATION_CITY_DOCKS, 2);
                $game->theah->duelActor = $actor;

                $game->stDuelGambleSetup();

                $events = $game->queued(EventGambleSetup::class);
                Assert::count(1, $events, 'event');
                Assert::same([$actor->Id, 2], [$events[0]->actorId, $events[0]->playerId], 'actor and controller');
                Assert::same(['processEvents'], $game->gamestate->transitions, 'transition');
            },

            'stDuelGambleRevealed reveals the top two faction cards by default' => function () {
                $game = new StatesTraitGame();
                $actor = $game->place(new GenericCharacter('Gambler'), Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->duelActor = $actor;
                $game->topFactionCards = [['id' => '11'], ['id' => '12'], ['id' => '13']];

                $game->stDuelGambleRevealed();

                $events = $game->queued(EventDuelGambleCardsRevealed::class);
                Assert::same([$actor->Id, 1, [11, 12]], [$events[0]->actorId, $events[0]->playerId, $events[0]->revealedCardIds], 'two cards, ids cast to int');
                Assert::same(['processEvents'], $game->gamestate->transitions, 'transition');
            },

            'stDuelGambleRevealed honours the reveal count and the from-bottom flag' => function () {
                $game = new StatesTraitGame();
                $actor = $game->place(new GenericCharacter('Gambler'), Game::LOCATION_CITY_DOCKS, 1);
                $game->theah->duelActor = $actor;
                $game->topFactionCards = [['id' => 11], ['id' => 12], ['id' => 13]];
                $game->bottomFactionCards = [['id' => 91], ['id' => 92], ['id' => 93]];
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 3);

                $game->stDuelGambleRevealed();
                Assert::same([11, 12, 13], $game->queued(EventDuelGambleCardsRevealed::class)[0]->revealedCardIds, 'top, count 3');

                $game->theah->takeQueuedEvents();
                $game->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);
                $game->stDuelGambleRevealed();
                Assert::same([91], $game->queued(EventDuelGambleCardsRevealed::class)[0]->revealedCardIds, 'bottom, count 1');
            },
        ];
    }
}
