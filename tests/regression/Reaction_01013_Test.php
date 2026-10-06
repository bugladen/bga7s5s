<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01013_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01013';
    }

    public function tests(): array
    {
        return [
            'offers reaction when own Red Hand destroyed at Vissenta location' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $redHand = $world->placeCharacter(
                    new GenericCharacter('Own RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $redHand->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($vissenta->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            // WHY regression: audit 2026-04-13 — must be YOUR Red Hand
            'does not offer for opponent Red Hand destroyed here' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $enemyRh = $world->placeCharacter(
                    new GenericCharacter('Enemy RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];
                $event = new EventCharacterDestroyed();
                $event->characterId = $enemyRh->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'enemy RH ignored');
            },

            'does not offer when Red Hand destroyed at different location' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $redHand = $world->placeCharacter(
                    new GenericCharacter('Own RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];
                $event = new EventCharacterDestroyed();
                $event->characterId = $redHand->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'different location');
            },

            // WHY regression: audit — handleEvent must gate on isAvailable()
            'does not offer when reaction already used' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                $redHand = $world->placeCharacter(
                    new GenericCharacter('Own RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];
                $reaction->Used = true;

                $event = new EventCharacterDestroyed();
                $event->characterId = $redHand->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used blocks offer');
            },

            'performReaction draws and marks used' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw queued');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'buttons include Draw Card and Pass' => function () {
                $world = new TestWorld();
                $vissenta = $world->placeCharacter(new _01013(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_01013 $reaction */
                $reaction = $vissenta->getReactions()[0];
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('drawCard', $ids, true), 'draw');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },
        ];
    }
}
