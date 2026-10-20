<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01027;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01027_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01027';
    }

    public function tests(): array
    {
        return [
            'offers when opposing pressure succeeds with difference 1 or less' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_HAND, 1);

                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $event = new EventLocationPressured();
                $event->playerId = 2;
                $event->success = true;
                $event->difference = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer on own pressure success' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $event = new EventLocationPressured();
                $event->playerId = 1;
                $event->success = true;
                $event->difference = 0;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'own pressure');
            },

            'does not offer when difference greater than 1' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $event = new EventLocationPressured();
                $event->playerId = 2;
                $event->success = true;
                $event->difference = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'diff too large');
            },

            'does not offer when not in hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $event = new EventLocationPressured();
                $event->playerId = 2;
                $event->success = true;
                $event->difference = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'performReaction failPressure queues pay state' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'failPressure');

                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'triggered failPressure replaces result with failed pressure' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01027(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01027 $reaction */
                $reaction = $risk->getReactions()[0];

                $pressured = new EventLocationPressured();
                $pressured->playerId = 2;
                $pressured->performerId = 99;
                $pressured->success = true;
                $pressured->difference = 1;
                $pressured->location = Game::LOCATION_CITY_DOCKS;
                $pressured->pressureType = 'Influence';
                $pressured->totalsExplanation = 'x';
                $pressured->highDramaBasicAction = false;
                $pressured->abilityId = 'abil';
                $pressured->theah = $world->theah;
                $reaction->handleEvent($pressured);
                $world->theah->takeQueuedEvents();

                $successResult = new EventLocationPressureResult();
                $successResult->playerId = 2;
                $successResult->success = true;
                $successResult->location = Game::LOCATION_CITY_DOCKS;
                $world->theah->queueEvent($successResult);

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'failPressure';
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $results = $world->theah->queuedOfType(EventLocationPressureResult::class);
                Assert::count(1, $results, 'one result');
                Assert::false($results[0]->success, 'failed');
                Assert::same(2, $results[0]->playerId, 'same pressurer');
            },
        ];
    }
}
