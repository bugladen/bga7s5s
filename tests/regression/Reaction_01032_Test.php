<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01032_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01032';
    }

    public function tests(): array
    {
        return [
            'offers when opposing targeting ability wounds your character' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->wounds = 1;
                $event->batchId = 42;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('use', $ids, true), 'use');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            // WHY regression: journal 2026-09-16 — Cirilo/self-target must not offer UL
            'does not offer when source is same controller' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($event->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer without Red Hand in play or Thug in hand' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($event->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'no cost pieces');
            },

            'does not offer when not in hand' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_PLAYER_HOME, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'use queues pay state' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');

                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass releases held wound and skips re-offer of same event' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->batchId = 7;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                $released = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $released, 'released');
                Assert::same($victim->Id, $released[0]->characterId, 'same wound');

                $again = new EventCharacterBeingWounded();
                $again->characterId = $victim->Id;
                $again->sourceId = $sibella->Id;
                $again->abilityId = $sibellaAction->Id;
                $again->batchId = 7;
                $again->theah = $world->theah;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'batch declined');
            },

            'cost destroy Red Hand cancels and marks used' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $rh = $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'destroy-' . $rh->Id);

                Assert::same($rh->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::true($reaction->Used, 'used');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound stays canceled');
            },

            'cost discard hand Thug' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Hand Thug', ['Thug']),
                    Game::LOCATION_HAND,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $victim->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'discard-' . $thug->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                Assert::true($reaction->Used, 'used');
            },

            'intercepts engage on your card from opposing targeting ability' => function () {
                $world = new TestWorld();
                $ul = $world->placeCard(new _01032(), Game::LOCATION_HAND, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01032 $reaction */
                $reaction = $ul->getReactions()[0];

                $event = new EventCardEngaged();
                $event->cardId = $own->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'engage held');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },
        ];
    }
}
