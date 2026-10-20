<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01042;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

/** StatesTrait: end of a duel round, choosing the next actor, and tearing the duel down. */
class StatesTrait_DuelEnd_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: duel end of round / next player / end';
    }

    /**
     * A duel at the Docks between challenger (player 1) and defender (player 2).
     * $actorIsChallenger picks whose round is ending.
     *
     * @return array{0:GenericCharacter,1:GenericCharacter,2:GenericCharacter,3:GenericCharacter} challenger, defender, actor, adversary
     */
    private function duel(StatesTraitGame $game, bool $actorIsChallenger = true, array $roundRow = []): array
    {
        $c = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $t = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $actor = $actorIsChallenger ? $c : $t;
        $adversary = $actorIsChallenger ? $t : $c;

        $game->globals->set(Game::DUEL_ID, 5);
        $game->globals->set(Game::DUEL_ROUND, 3);
        $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
        $game->duelRow = [
            'challenging_player_id' => 1,
            'challenger_id' => $c->Id,
            'defending_player_id' => 2,
            'defender_id' => $t->Id,
        ];
        $game->duelRoundRow = $roundRow + [
            'actor_id' => $actor->Id,
            'ending_challenger_threat' => 0,
            'ending_defender_threat' => 0,
            'challenger_threat_is_lethal' => 0,
            'defender_threat_is_lethal' => 0,
        ];
        $game->globals->set(Game::DUEL_CURRENT_PLAYER, $actor->ControllerId);
        $game->theah->duelActor = $actor;
        $game->theah->duelOpponent = $adversary;
        // The harness stub treats the actor as the challenger unless told otherwise.
        $game->theah->duelChallengerIdOverride = $c->Id;
        return [$c, $t, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            // ---- stDuelEndOfRound: leftover threat -> wounds ------------------------------------

            'stDuelEndOfRound wounds the actor with the leftover threat and resets their pool' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game, true, ['ending_challenger_threat' => 1]);

                $game->stDuelEndOfRound();

                $wounds = $game->queued(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound event');
                Assert::same([$c->Id, $t->Id, 1], [$wounds[0]->characterId, $wounds[0]->sourceId, $wounds[0]->wounds], 'actor wounded by the adversary');
                Assert::contains('1 Threat was left over', $wounds[0]->reason, 'reason');
                Assert::false(str_contains($wounds[0]->reason, 'Restricted Hostilities'), 'no cap needed');
                Assert::same(0, $game->duelRoundRow['ending_challenger_threat'], 'pool reset');
                Assert::same(0, $game->duelRoundRow['challenger_threat_is_lethal'], 'lethal flag reset');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuelEndOfRound applies Restricted Hostilities: wounds are capped at the adversary stat for the duel stat' => function () {
                foreach ([
                    'combat' => [Game::STAT_COMBAT, 'ModifiedCombat', 'Combat'],
                    'finesse' => [Game::STAT_FINESSE, 'ModifiedFinesse', 'Finesse'],
                    'influence' => [Game::STAT_INFLUENCE, 'ModifiedInfluence', 'Influence'],
                ] as $label => [$stat, $modified, $name]) {
                    $game = new StatesTraitGame();
                    [$c, $t, $actor, $adversary] = $this->duel($game, true, ['ending_challenger_threat' => 5]);
                    $game->globals->set(Game::CHALLENGE_STAT, $stat);
                    $adversary->$modified = 2;

                    $game->stDuelEndOfRound();

                    $wound = $game->queued(EventCharacterBeingWounded::class)[0];
                    Assert::same(2, $wound->wounds, "{$label}: capped at 2");
                    Assert::contains('Restricted Hostilities (Stat value of 2)', $wound->reason, "{$label}: reduction explained");
                    Assert::contains("Stat Used for Duel is {$name}", $wound->reason, "{$label}: stat named");
                }
            },

            'stDuelEndOfRound does not cap LETHAL threat' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, true, ['ending_challenger_threat' => 5, 'challenger_threat_is_lethal' => 1]);

                $game->stDuelEndOfRound();

                $wound = $game->queued(EventCharacterBeingWounded::class)[0];
                Assert::same(5, $wound->wounds, 'full wounds');
                Assert::contains('Threat was LETHAL', $wound->reason, 'lethal noted');
            },

            'stDuelEndOfRound uses the defender fields when the defender is the actor' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game, false, ['ending_defender_threat' => 1, 'ending_challenger_threat' => 0]);

                $game->stDuelEndOfRound();

                $wound = $game->queued(EventCharacterBeingWounded::class)[0];
                Assert::same([$t->Id, $c->Id, 1], [$wound->characterId, $wound->sourceId, $wound->wounds], 'defender wounded by the challenger');
                Assert::same(0, $game->duelRoundRow['ending_defender_threat'], 'defender pool reset');
            },

            'stDuelEndOfRound queues no wound when the actor has no threat left, but still ends the round' => function () {
                $game = new StatesTraitGame();
                [$c] = $this->duel($game);

                $game->stDuelEndOfRound();

                Assert::count(0, $game->queued(EventCharacterBeingWounded::class), 'no wound');
                $end = $game->queued(EventDuelEndOfRound::class);
                Assert::same([1, $c->Id], [$end[0]->playerId, $end[0]->actorId], 'DuelEndOfRound for the actor controller');
            },

            'stDuelEndOfRound commits the wound before the DuelEndOfRound event (movers run afterwards)' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, true, ['ending_challenger_threat' => 1]);

                $game->stDuelEndOfRound();

                Assert::same(
                    [EventCharacterBeingWounded::class, EventDuelEndOfRound::class],
                    array_map(fn($e) => $e::class, $game->theah->queuedEvents),
                    'wound first'
                );
            },

            // ---- stDuelEndOfRound: adversary threat nullification ----------------------------------

            'stDuelEndOfRound keeps the adversary pool when both are still at the same location' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, true, ['ending_defender_threat' => 4]);

                $game->stDuelEndOfRound();

                Assert::same(4, $game->duelRoundRow['ending_defender_threat'], 'adversary pool untouched');
                Assert::count(0, $game->notify->messages, 'no nullify message');
            },

            'stDuelEndOfRound nullifies adversary threat when a living actor is no longer co-located' => function () {
                $game = new StatesTraitGame();
                [$c, $t, $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 4, 'ending_challenger_threat' => 1]);
                $adversary->Location = 'Elsewhere';

                $game->stDuelEndOfRound();

                Assert::same(0, $game->duelRoundRow['ending_defender_threat'], 'adversary pool nullified');
                Assert::contains('nullified', $game->notify->messages[0]['message'], 'message');
                Assert::count(1, $game->queued(EventCharacterBeingWounded::class), 'the actor still takes their own leftover threat');
            },

            'stDuelEndOfRound nullifies the challenger pool when the defender is the actor' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, false, ['ending_challenger_threat' => 4]);
                $adversary->Location = 'Elsewhere';

                $game->stDuelEndOfRound();

                Assert::same(0, $game->duelRoundRow['ending_challenger_threat'], 'challenger pool nullified');
            },

            'stDuelEndOfRound keeps adversary threat when only the actor died elsewhere (locker is not a location)' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 4]);
                $actor->Location = 'Locker-1';

                $game->stDuelEndOfRound();

                Assert::same(4, $game->duelRoundRow['ending_defender_threat'], 'dead actor does not wipe the pool');
            },

            'stDuelEndOfRound nullifies adversary threat when both characters are dead' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 4]);
                $actor->Location = 'Locker-1';
                $adversary->Location = 'Locker-2';

                $game->stDuelEndOfRound();

                Assert::same(0, $game->duelRoundRow['ending_defender_threat'], 'pool nullified');
            },

            'stDuelEndOfRound: a Brute is dead in the Discard pile, not the Locker' => function () {
                $game = new StatesTraitGame();
                $c = $game->place(new GenericCharacter('Brute', ['Brute']), 'Discard-1', 1);
                $t = $game->place(new GenericCharacter('Foe'), 'Locker-2', 2);
                $game->globals->set(Game::DUEL_ID, 5);
                $game->globals->set(Game::DUEL_ROUND, 1);
                $game->duelRow = ['challenger_id' => $c->Id, 'defender_id' => $t->Id];
                $game->duelRoundRow = [
                    'actor_id' => $c->Id, 'ending_challenger_threat' => 0, 'ending_defender_threat' => 3,
                    'challenger_threat_is_lethal' => 0, 'defender_threat_is_lethal' => 0,
                ];
                $game->theah->duelActor = $c;
                $game->theah->duelOpponent = $t;

                $game->stDuelEndOfRound();

                Assert::same(0, $game->duelRoundRow['ending_defender_threat'], 'both dead -> nullified');
            },

            'stDuelEndOfRound clears the per-round globals' => function () {
                $game = new StatesTraitGame();
                [$c, $t, $actor] = $this->duel($game);
                foreach ([
                    Game::CHOSEN_TECHNIQUE_IS_MAIN, Game::CHOSEN_TECHNIQUE, Game::CHOSEN_MANEUVER, Game::CHOSEN_CARD,
                    Game::CHOSEN_CARD_COST, Game::NEXT_COMBAT_CARD, Game::UNRAVEL_THE_THREAD_CONTROLLER_ID, Game::DISCOUNT,
                    Game::REVEALED_CARDS, Game::DUEL_GAMBLED, Game::DUEL_MANUEVER_ID, Game::GAMBLE_TYPE,
                    Game::ROLL_THE_BONES_ACTIVATED, Game::ROLL_THE_BONES_CARD_ID, Game::GAMBLE_REVEAL_COUNT,
                    Game::GAMBLE_REVEAL_EXPLANATIONS, Game::GAMBLE_REVEAL_FROM_BOTTOM,
                ] as $key) {
                    $game->globals->set($key, 1);
                }
                $game->globals->set(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, [
                    ['adversaryId' => $actor->Id],
                    ['adversaryId' => 9999],
                ]);

                $game->stDuelEndOfRound();

                foreach ([
                    Game::CHOSEN_TECHNIQUE_IS_MAIN, Game::CHOSEN_TECHNIQUE, Game::CHOSEN_MANEUVER, Game::CHOSEN_CARD,
                    Game::CHOSEN_CARD_COST, Game::NEXT_COMBAT_CARD, Game::UNRAVEL_THE_THREAD_CONTROLLER_ID, Game::DISCOUNT,
                    Game::REVEALED_CARDS, Game::DUEL_GAMBLED, Game::DUEL_MANUEVER_ID, Game::GAMBLE_TYPE,
                    Game::ROLL_THE_BONES_ACTIVATED, Game::ROLL_THE_BONES_CARD_ID, Game::GAMBLE_REVEAL_COUNT,
                    Game::GAMBLE_REVEAL_EXPLANATIONS, Game::GAMBLE_REVEAL_FROM_BOTTOM,
                ] as $key) {
                    Assert::false($game->globals->has($key), "{$key} cleared");
                }
                Assert::same(
                    [['adversaryId' => 9999]],
                    $game->globals->get(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS),
                    "only the finished actor's Mireli's Revision entry expires"
                );
                Assert::true($game->globals->has(Game::DUEL_ID), 'the duel itself continues');
            },

            // ---- stDuelNextPlayer -----------------------------------------------------------------

            'stDuelNextPlayer ends the duel when no threat remains anywhere' => function () {
                $game = new StatesTraitGame();
                $this->duel($game);
                $game->globals->set(Game::DUEL_CURRENT_PLAYER, 1);

                $game->stDuelNextPlayer();

                Assert::same(['endOfDuel'], $game->gamestate->transitions, 'transition');
                Assert::same([], $game->gamestate->activePlayerChanges, 'no player change');
                Assert::contains('No Threat remains', $game->notify->messages[0]['message'], 'message');
                Assert::same([1], $game->extraTimeGiven, 'extra time to the player who just acted');
            },

            'stDuelNextPlayer continues when either pool still has threat: challenger actor hands over to the defender' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, true, ['ending_defender_threat' => 2]);
                $game->globals->set(Game::DUEL_CURRENT_PLAYER, 1);

                $game->stDuelNextPlayer();

                Assert::same(['newRound'], $game->gamestate->transitions, 'transition');
                Assert::same([2], $game->gamestate->activePlayerChanges, 'defending player becomes active');
                Assert::same(2, $game->globals->get(Game::DUEL_CURRENT_PLAYER), 'current duel player');
            },

            'stDuelNextPlayer: a defender actor hands over to the challenging player' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, false, ['ending_challenger_threat' => 1]);
                $game->globals->set(Game::DUEL_CURRENT_PLAYER, 2);

                $game->stDuelNextPlayer();

                Assert::same(['newRound'], $game->gamestate->transitions, 'transition');
                Assert::same([1], $game->gamestate->activePlayerChanges, 'challenging player becomes active');
                Assert::same(1, $game->globals->get(Game::DUEL_CURRENT_PLAYER), 'current duel player');
            },

            'stDuelNextPlayer: carry-over pending threat alone keeps the duel going' => function () {
                foreach ([Game::PENDING_CHALLENGER_THREAT, Game::PENDING_DEFENDER_THREAT] as $key) {
                    $game = new StatesTraitGame();
                    $this->duel($game);
                    $game->globals->set($key, 2);

                    $game->stDuelNextPlayer();

                    Assert::same(['newRound'], $game->gamestate->transitions, "{$key} continues");
                }
            },

            'stDuelNextPlayer ends the duel when the adversary has left play' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 2]);
                $game->discardOrLockerByCardId[$adversary->Id] = true;

                $game->stDuelNextPlayer();

                Assert::same(['endOfDuel'], $game->gamestate->transitions, 'transition');
                Assert::contains('no longer present', $game->notify->messages[0]['message'], 'message');
            },

            'stDuelNextPlayer ends the duel when the adversary no longer exists' => function () {
                $game = new StatesTraitGame();
                $this->duel($game, true, ['ending_defender_threat' => 2]);
                $game->theah->duelOpponent = null;

                $game->stDuelNextPlayer();

                Assert::same(['endOfDuel'], $game->gamestate->transitions, 'transition');
            },

            'stDuelNextPlayer ends the duel when a living actor is no longer co-located with the adversary' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 2]);
                $adversary->Location = 'Elsewhere';

                $game->stDuelNextPlayer();

                Assert::same(['endOfDuel'], $game->gamestate->transitions, 'flee/split ends the duel');
            },

            // WHY: a dead actor sits in the Locker, which never equals the adversary's city location;
            // without the exception the duel would end while adversary threat is still pending.
            'stDuelNextPlayer continues when the actor died but the adversary is still present' => function () {
                $game = new StatesTraitGame();
                [, , $actor, $adversary] = $this->duel($game, true, ['ending_defender_threat' => 2]);
                $actor->Location = 'Locker-1';
                $game->discardOrLockerByCardId[$actor->Id] = true;

                $game->stDuelNextPlayer();

                Assert::same(['newRound'], $game->gamestate->transitions, 'duel continues');
                Assert::same([2], $game->gamestate->activePlayerChanges, 'defender next');
            },

            // ---- stDuelEnd --------------------------------------------------------------------------

            'stDuelEnd queues DuelEnd and ActionResolved for the challenging player, then advances' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);

                $game->stDuelEnd();

                $end = $game->queued(EventDuelEnd::class);
                Assert::count(1, $end, 'DuelEnd');
                Assert::same([1, $c->Id, 2, $t->Id], [$end[0]->challengingPlayerId, $end[0]->challengerId, $end[0]->defendingPlayerId, $end[0]->defenderId], 'participants');
                $resolved = $game->queued(EventActionResolved::class);
                Assert::same(1, $resolved[0]->playerId, 'challenger controller');
                Assert::same(
                    [EventDuelEnd::class, EventActionResolved::class],
                    array_map(fn($e) => $e::class, $game->theah->queuedEvents),
                    'DuelEnd precedes ActionResolved'
                );
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            'stDuelEnd clears every duel-scoped global' => function () {
                $game = new StatesTraitGame();
                $this->duel($game);
                $keys = [
                    Game::CHALLENGE_CANCELLED, Game::DUEL_CURRENT_PLAYER, Game::CHALLENGER_THREAT, Game::DEFENDER_THREAT,
                    Game::DEFENDER_THREAT_IS_LETHAL, Game::CHALLENGE_ACCEPTED, Game::DUEL_CHALLENGER, Game::DUEL_DEFENDER,
                    Game::DUEL_GAMBLED, Game::GAMBLE_TYPE, Game::ROLL_THE_BONES_ACTIVATED, Game::ROLL_THE_BONES_CARD_ID,
                    Game::GAMBLE_REVEAL_COUNT, Game::GAMBLE_REVEAL_EXPLANATIONS, Game::GAMBLE_REVEAL_FROM_BOTTOM,
                    Game::ABNORMAL_FLOW, Game::PENDING_CHALLENGER_THREAT, Game::PENDING_DEFENDER_THREAT,
                    Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, Game::BORETS_MANEUVER_TECHNIQUE_LOCK,
                    Game::DUEL_PENDING_MANEUVER_CARD, Game::DUEL_MANUEVER_ID, Game::NEXT_COMBAT_CARD,
                ];
                foreach ($keys as $key) {
                    $game->globals->set($key, 1);
                }
                $game->globals->set(Game::IN_DUEL, true);

                // DUEL_ID / DUEL_ROUND / CHALLENGE_STAT are read for the duel row first, then removed.
                $game->stDuelEnd();

                foreach ($keys as $key) {
                    Assert::false($game->globals->has($key), "{$key} cleared");
                }
                foreach ([Game::DUEL_ID, Game::DUEL_ROUND, Game::CHALLENGE_STAT] as $key) {
                    Assert::false($game->globals->has($key), "{$key} cleared");
                }
                Assert::false($game->globals->get(Game::IN_DUEL), 'no longer in a duel');
                Assert::same(1, $game->clearLastKnownCalls, 'last-known participants cleared');
            },

            'stDuelEnd discards every card left in the Dueling Line to its owner' => function () {
                $game = new StatesTraitGame();
                [$c, $t] = $this->duel($game);
                $mine = $game->place(new GenericAttachment('Mine'), Game::LOCATION_DUELING_LINE, 1);
                $theirs = $game->place(new GenericAttachment('Theirs'), Game::LOCATION_DUELING_LINE, 2);
                $game->duelingLineRows = [['id' => $mine->Id], ['id' => $theirs->Id]];

                $game->stDuelEnd();

                $discards = $game->queued(EventCardDiscardedFromHand::class);
                Assert::same(
                    [[1, $mine->Id, 0], [2, $theirs->Id, 0]],
                    array_map(fn($e) => [$e->ownerId, $e->cardId, $e->sourceId], $discards),
                    'owner discards, framework source'
                );
                Assert::count(0, $game->queued(EventCardAddedToHand::class), 'nothing returned to hand');
            },

            'stDuelEnd (Terrell Brandt) returns his own attachments to hand; everything else is discarded' => function () {
                $game = new StatesTraitGame();
                $terrell = $game->place(new _01042(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::DUEL_ID, 5);
                $game->duelRow = [
                    'challenging_player_id' => 1, 'challenger_id' => $terrell->Id,
                    'defending_player_id' => 2, 'defender_id' => $foe->Id,
                ];
                $weapon = $game->place(new GenericAttachment('His weapon'), Game::LOCATION_DUELING_LINE, 1);
                $foeWeapon = $game->place(new GenericAttachment('Their weapon'), Game::LOCATION_DUELING_LINE, 2);
                $combatCard = $game->place(new GenericCharacter('Not an attachment'), Game::LOCATION_DUELING_LINE, 1);
                $game->duelingLineRows = [['id' => $weapon->Id], ['id' => $foeWeapon->Id], ['id' => $combatCard->Id]];

                $game->stDuelEnd();

                $returned = $game->queued(EventCardAddedToHand::class);
                Assert::same([$weapon->Id], array_map(fn($e) => $e->cardId, $returned), 'only Terrell attachment returns');
                Assert::same(1, $returned[0]->playerId, 'to Terrell controller');
                $discarded = array_map(fn($e) => $e->cardId, $game->queued(EventCardDiscardedFromHand::class));
                Assert::same([$foeWeapon->Id, $combatCard->Id], $discarded, 'the rest are discarded');
                Assert::contains('Attachments in his dueling line will be returned', $game->notify->messages[0]['message'], 'announced');
            },

            'stDuelEnd (Terrell Brandt as defender) also keeps his attachments' => function () {
                $game = new StatesTraitGame();
                $foe = $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 1);
                $terrell = $game->place(new _01042(), Game::LOCATION_CITY_DOCKS, 2);
                $game->duelRow = [
                    'challenging_player_id' => 1, 'challenger_id' => $foe->Id,
                    'defending_player_id' => 2, 'defender_id' => $terrell->Id,
                ];
                $game->globals->set(Game::DUEL_ID, 5);
                $weapon = $game->place(new GenericAttachment('His weapon'), Game::LOCATION_DUELING_LINE, 2);
                $game->duelingLineRows = [['id' => $weapon->Id]];

                $game->stDuelEnd();

                Assert::same([$weapon->Id], array_map(fn($e) => $e->cardId, $game->queued(EventCardAddedToHand::class)), 'returned');
            },

            'stDuelEnd discards even Terrell attachments once he has been destroyed' => function () {
                $game = new StatesTraitGame();
                $terrell = $game->place(new _01042(), 'Locker-1', 1);
                $foe = $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $game->discardOrLockerByCardId[$terrell->Id] = true;
                $game->globals->set(Game::DUEL_ID, 5);
                $game->duelRow = [
                    'challenging_player_id' => 1, 'challenger_id' => $terrell->Id,
                    'defending_player_id' => 2, 'defender_id' => $foe->Id,
                ];
                $weapon = $game->place(new GenericAttachment('His weapon'), Game::LOCATION_DUELING_LINE, 1);
                $game->duelingLineRows = [['id' => $weapon->Id]];

                $game->stDuelEnd();

                Assert::count(0, $game->queued(EventCardAddedToHand::class), 'nothing returned');
                Assert::same([$weapon->Id], array_map(fn($e) => $e->cardId, $game->queued(EventCardDiscardedFromHand::class)), 'discarded');
            },

            // ---- stFromCard ---------------------------------------------------------------------------

            'stFromCard hands the transition source, state name and internal id to the card' => function () {
                $game = new StatesTraitGame();
                $probe = new class extends GenericCharacter {
                    /** @var list<array> */
                    public array $calls = [];

                    public function stateFromCard(Game $game, int $state, string $stateName, string $internalId): void
                    {
                        $this->calls[] = [$state, $stateName, $internalId];
                    }
                };
                $game->place($probe, Game::LOCATION_CITY_DOCKS, 1);
                $game->gamestate->currentMainStateId = 4321;
                $game->gamestate->currentMainStateName = 'someState';
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $probe->Id);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, 'abc');

                $game->stFromCard();

                Assert::same([[4321, 'someState', 'abc']], $probe->calls, 'delegated once');
                Assert::same(1, $game->theah->buildCityCalls, 'city rebuilt first');
            },

            'stFromCard defaults the internal id to an empty string' => function () {
                $game = new StatesTraitGame();
                $probe = new class extends GenericCharacter {
                    public ?string $seen = null;

                    public function stateFromCard(Game $game, int $state, string $stateName, string $internalId): void
                    {
                        $this->seen = $internalId;
                    }
                };
                $game->place($probe, Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, $probe->Id);

                $game->stFromCard();

                Assert::same('', $probe->seen, 'empty internal id');
            },
        ];
    }
}
