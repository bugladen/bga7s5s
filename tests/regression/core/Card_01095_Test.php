<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01095;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01095b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class Card_01095_Test extends TestCase
{
    public function name(): string
    {
        return '_01095 Patricia Moustakas';
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    private function blocked(callable $fn): bool
    {
        try {
            $fn();
        } catch (\BgaUserException $e) {
            return true;
        }
        return false;
    }

    /** @return array{0:_01095,1:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character} Patricia (P1, en garde) at the Docks and an opposing challenger. */
    private function scene(TestWorld $world): array
    {
        $patricia = $world->placeCharacter(new _01095(), Game::LOCATION_CITY_DOCKS, 1);
        $patricia->Engaged = false;
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
        return [$patricia, $challenger];
    }

    public function tests(): array
    {
        return [
            'constructs Dockmaster Numa with both City Actions' => function () {
                $patricia = new _01095();
                Assert::instanceOf(IHasActions::class, $patricia, 'actions');
                Assert::same(4, $patricia->Resolve, 'Resolve');
                Assert::same(1, $patricia->Combat, 'Combat');
                Assert::same(2, $patricia->Finesse, 'Finesse');
                Assert::same(1, $patricia->Influence, 'Influence');
                Assert::true($patricia->hasTrait('Dockmaster'), 'Dockmaster');
                Assert::true($patricia->hasTrait('Numa'), 'Numa');
                Assert::true($patricia->hasFaction('Castille'), 'Castille');
                Assert::count(2, $patricia->getActions(), 'two actions');
                Assert::instanceOf(Action_01095a::class, $patricia->getActions()[0], 'claim action first');
                Assert::instanceOf(Action_01095b::class, $patricia->getActions()[1], 'engage action second');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _01095(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($patricia->Id . '_Action_01095a', $patricia->getActions()[0]->Id, '95a');
                Assert::same($patricia->Id . '_Action_01095b', $patricia->getActions()[1]->Id, '95b');
            },

            // WHY: printed passive - at the Docks and en garde (not Engaged) she cannot be issued challenges.
            'cannot be challenged at the Docks while en garde' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);

                $event = $this->issued($world, $challenger->Id, $patricia->Id);
                Assert::true($this->blocked(fn() => $patricia->eventCheck($event)), 'challenge refused');
            },

            'can be challenged at the Docks once engaged' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);
                $patricia->Engaged = true;

                $event = $this->issued($world, $challenger->Id, $patricia->Id);
                Assert::false($this->blocked(fn() => $patricia->eventCheck($event)), 'engaged is vulnerable');
            },

            'can be challenged elsewhere while en garde' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);
                $patricia->Location = Game::LOCATION_CITY_FORUM;
                $challenger->Location = Game::LOCATION_CITY_FORUM;

                $event = $this->issued($world, $challenger->Id, $patricia->Id);
                Assert::false($this->blocked(fn() => $patricia->eventCheck($event)), 'not at the Docks');
            },

            'can be challenged at Home while en garde' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);
                $patricia->Location = Game::LOCATION_PLAYER_HOME;

                $event = $this->issued($world, $challenger->Id, $patricia->Id);
                Assert::false($this->blocked(fn() => $patricia->eventCheck($event)), 'Home');
            },

            // WHY: the guard keys on defenderId - other characters at the Docks stay challengeable.
            'her allies at the Docks can still be challenged' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->issued($world, $challenger->Id, $ally->Id);
                Assert::false($this->blocked(fn() => $patricia->eventCheck($event)), 'ally');
            },

            'she may still issue challenges herself' => function () {
                $world = new TestWorld();
                [$patricia, $challenger] = $this->scene($world);

                $event = $this->issued($world, $patricia->Id, $challenger->Id);
                $event->playerId = 1;
                Assert::false($this->blocked(fn() => $patricia->eventCheck($event)), 'as challenger');
            },

            'unrelated events pass through eventCheck' => function () {
                $world = new TestWorld();
                [$patricia] = $this->scene($world);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                Assert::false($this->blocked(fn() => $patricia->eventCheck($dusk)), 'dusk');
            },
        ];
    }
}
