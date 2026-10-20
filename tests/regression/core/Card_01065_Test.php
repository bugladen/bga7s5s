<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;

class Card_01065_Test extends TestCase
{
    public function name(): string
    {
        return '_01065 Henri Michelet';
    }

    private function moveEvent(TestWorld $world, int $initiator, int $cardId, string $from, string $to)
    {
        $event = EventFactory::createCardMovingEvent($initiator, $cardId, $from, $to, false);
        $event->theah = $world->theah;
        return $event;
    }

    private function blocked(callable $fn): bool
    {
        try {
            $fn();
        } catch (UserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'constructs Musketeer Duelist with Reaction_01065' => function () {
                $henri = new _01065();
                Assert::instanceOf(IHasReactions::class, $henri, 'reactions');
                Assert::same(4, $henri->Resolve, 'Resolve');
                Assert::same(3, $henri->Combat, 'Combat');
                Assert::same(3, $henri->Finesse, 'Finesse');
                Assert::same(1, $henri->Influence, 'Influence');
                Assert::true($henri->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($henri->hasTrait('Duelist'), 'Duelist');
                Assert::true($henri->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Reaction_01065::class, $henri->getReactions()[0], 'Reaction_01065');
            },

            // WHY: printed lock - "other Musketeers at Henri's location cannot be moved by an opponent's abilities".
            'opponent cannot move another Musketeer from Henri location' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->moveEvent($world, 2, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM);
                Assert::true($this->blocked(fn() => $henri->eventCheck($event)), 'blocked');
            },

            'opponent cannot move a Musketeer Home from Henri location' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->moveEvent($world, 2, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME);
                Assert::true($this->blocked(fn() => $henri->eventCheck($event)), 'blocked');
            },

            'owner can still move their own Musketeer' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->moveEvent($world, 1, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM);
                Assert::false($this->blocked(fn() => $henri->eventCheck($event)), 'own move allowed');
            },

            'opponent can move Henri himself' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->moveEvent($world, 2, $henri->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM);
                Assert::false($this->blocked(fn() => $henri->eventCheck($event)), '"other" Musketeers only');
            },

            'opponent can move non-Musketeers at Henri location' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->moveEvent($world, 2, $plain->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM);
                Assert::false($this->blocked(fn() => $henri->eventCheck($event)), 'non-Musketeer');
            },

            'opponent can move Musketeers at other locations' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far', ['Musketeer']), Game::LOCATION_CITY_FORUM, 1);

                $event = $this->moveEvent($world, 2, $far->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR);
                Assert::false($this->blocked(fn() => $henri->eventCheck($event)), 'other location');
            },

            'opponent Musketeers are not protected' => function () {
                $world = new TestWorld();
                $henri = $world->placeCharacter(new _01065(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);

                $event = $this->moveEvent($world, 2, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM);
                Assert::false($this->blocked(fn() => $henri->eventCheck($event)), 'their own');
            },
        ];
    }
}
