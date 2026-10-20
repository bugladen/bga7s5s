<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01037_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01037';
    }

    public function tests(): array
    {
        return [
            'duel start stores participant ids' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_FORUM, 1);
                $defender = $world->placeCharacter(new GenericCharacter('D'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01037 $reaction */
                $reaction = $edeline->getReactions()[0];
                $event = new EventDuelStarted();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $defender->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $challengerProp = new \ReflectionProperty(Reaction_01037::class, 'ChallengerId');
                $challengerProp->setAccessible(true);
                $defenderProp = new \ReflectionProperty(Reaction_01037::class, 'DefenderId');
                $defenderProp->setAccessible(true);
                Assert::same($challenger->Id, $challengerProp->getValue($reaction), 'challenger');
                Assert::same($defender->Id, $defenderProp->getValue($reaction), 'defender');
            },

            'duel end offers when engaged participant at adjacent location' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_FORUM, 1);
                $defender = $world->placeCharacter(new GenericCharacter('D'), Game::LOCATION_CITY_BAZAAR, 2);
                $challenger->Engaged = true;

                /** @var Reaction_01037 $reaction */
                $reaction = $edeline->getReactions()[0];
                $challengerProp = new \ReflectionProperty(Reaction_01037::class, 'ChallengerId');
                $challengerProp->setAccessible(true);
                $defenderProp = new \ReflectionProperty(Reaction_01037::class, 'DefenderId');
                $defenderProp->setAccessible(true);
                $challengerProp->setValue($reaction, $challenger->Id);
                $defenderProp->setValue($reaction, $defender->Id);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'duel end does not offer when participants not adjacent or not engaged' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_GOVERNORS_GARDEN, 1);
                $defender = $world->placeCharacter(new GenericCharacter('D'), Game::LOCATION_CITY_GOVERNORS_GARDEN, 2);
                $challenger->Engaged = true;
                $defender->Engaged = true;

                /** @var Reaction_01037 $reaction */
                $reaction = $edeline->getReactions()[0];
                $challengerProp = new \ReflectionProperty(Reaction_01037::class, 'ChallengerId');
                $challengerProp->setAccessible(true);
                $defenderProp = new \ReflectionProperty(Reaction_01037::class, 'DefenderId');
                $defenderProp->setAccessible(true);
                $challengerProp->setValue($reaction, $challenger->Id);
                $defenderProp->setValue($reaction, $defender->Id);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not adjacent');
            },

            'performReaction moves engaged participant to Edeline' => function () {
                $world = new TestWorld();
                $edeline = $world->placeCharacter(new _01037(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_FORUM, 2);
                $challenger->Engaged = true;

                /** @var Reaction_01037 $reaction */
                $reaction = $edeline->getReactions()[0];
                $challengerProp = new \ReflectionProperty(Reaction_01037::class, 'ChallengerId');
                $challengerProp->setAccessible(true);
                $defenderProp = new \ReflectionProperty(Reaction_01037::class, 'DefenderId');
                $defenderProp->setAccessible(true);
                $challengerProp->setValue($reaction, $challenger->Id);
                $defenderProp->setValue($reaction, 0);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'move-' . $challenger->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($challenger->Id, $moves[0]->cardId, 'target');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to Edeline');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
