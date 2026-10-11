<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01203;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01203;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01203_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01203';
    }

    /**
     * Leja at Docks; duel participants at Docks (harness challenger=duelActor, defender=duelOpponent).
     *
     * @return array{0:_01203,1:Reaction_01203,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $leja = $world->placeCharacter(new _01203(), Game::LOCATION_CITY_DOCKS, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $challenger;
        $world->theah->duelOpponent = $defender;
        $world->game->globals->set(Game::CHALLENGER_THREAT, 1);
        $world->game->globals->set(Game::DEFENDER_THREAT, 0);
        /** @var Reaction_01203 $reaction */
        $reaction = $leja->getReactions()[0];
        return [$leja, $reaction, $challenger, $defender];
    }

    private function roundOne(TestWorld $world, int $challengerId, int $defenderId): EventDuelNewRound
    {
        $event = new EventDuelNewRound();
        $event->round = 1;
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01203(), 'CardReaction');
            },

            // WHY (journal 2026-04-08-09): remove buttons only when that side has threat > 0.
            'buttons offer +threat to both, -threat only where current > 0, and pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same([
                    'addThreatChallenger',
                    'addThreatDefender',
                    'removeThreatChallenger',
                    'pass',
                ], $ids, 'buttons');
            },

            'first-round NewRound offers when a duel is at Leja\'s location' => function () {
                $world = new TestWorld();
                [$leja, $reaction, $challenger, $defender] = $this->scene($world);

                $reaction->handleEvent($this->roundOne($world, $challenger->Id, $defender->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($leja->Id, $transitions[0]->sourceId, 'Leja');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer on round 2' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);

                $event = $this->roundOne($world, $challenger->Id, $defender->Id);
                $event->round = 2;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'round 2');
            },

            'does not offer when the duel is elsewhere' => function () {
                $world = new TestWorld();
                [$leja, $reaction, $challenger, $defender] = $this->scene($world);
                $leja->Location = Game::LOCATION_CITY_BAZAAR;

                $reaction->handleEvent($this->roundOne($world, $challenger->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            'does not offer when Leja is uncontrolled' => function () {
                $world = new TestWorld();
                [$leja, $reaction, $challenger, $defender] = $this->scene($world);
                $leja->ControllerId = 0;

                $reaction->handleEvent($this->roundOne($world, $challenger->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->roundOne($world, $challenger->Id, $defender->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'addThreatChallenger queues +1 challenger threat and marks Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'addThreatChallenger');

                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'threat');
                Assert::same(1, $threat[0]->challengerThreat, '+1 challenger');
                Assert::same(0, $threat[0]->defenderThreat, 'defender unchanged');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'addThreatDefender queues +1 defender threat' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'addThreatDefender');

                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(0, $threat->challengerThreat, 'challenger');
                Assert::same(1, $threat->defenderThreat, '+1 defender');
                Assert::true($reaction->Used, 'used');
            },

            'removeThreatChallenger queues -1 challenger threat' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'removeThreatChallenger');

                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(-1, $threat->challengerThreat, '-1 challenger');
                Assert::same(0, $threat->defenderThreat, 'defender');
                Assert::true($reaction->Used, 'used');
            },

            'removeThreatDefender queues -1 defender threat' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'removeThreatDefender');

                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(0, $threat->challengerThreat, 'challenger');
                Assert::same(-1, $threat->defenderThreat, '-1 defender');
                Assert::true($reaction->Used, 'used');
            },

            // WHY (journal 2026-04-08-09): Pass must not consume the once-per-day reaction.
            'pass leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'no threat');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
