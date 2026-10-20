<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01015_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01015';
    }

    public function tests(): array
    {
        return [
            // WHY regression: audit 2026-04-13 — any character destroyed, not only own
            'offers reaction when any character destroyed while scheme at Home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01015 $reaction */
                $reaction = $scheme->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $enemy->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'scheme source');
            },

            // WHY: scheme must still be at Home (not dusk-locker mid-batch)
            'does not offer when scheme is not at Player Home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_CITY_LOCKER, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Reaction_01015 $reaction */
                $reaction = $scheme->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'locker scheme silent');
            },

            'does not offer when reaction already used' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Reaction_01015 $reaction */
                $reaction = $scheme->getReactions()[0];
                $reaction->Used = true;

                $event = new EventCharacterDestroyed();
                $event->characterId = $victim->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used blocks');
            },

            'performReaction draws, marks used, clears queued transitions' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_01015 $reaction */
                $reaction = $scheme->getReactions()[0];

                // Simulate multiple destroy-queued transitions
                $extra = new EventTransition();
                $extra->internalId = $reaction->Id;
                $extra->sourceId = $scheme->Id;
                $world->theah->queueEvent($extra);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::true($reaction->Used, 'used');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'transitions cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'buttons include Draw Card and Pass' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_01015 $reaction */
                $reaction = $scheme->getReactions()[0];
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('drawCard', $ids, true), 'draw');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },
        ];
    }
}
