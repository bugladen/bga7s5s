<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01116;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_01116_Test extends TestCase
{
    public function name(): string
    {
        return '_01116 Yevgeni';
    }

    private function calcStats(TestWorld $world, int $actorId): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Leader with both Reactions' => function () {
                $yevgeni = new _01116();
                Assert::instanceOf(Leader::class, $yevgeni, 'Leader');
                Assert::instanceOf(IHasReactions::class, $yevgeni, 'reactions');
                Assert::same(12, $yevgeni->Resolve, 'Resolve');
                Assert::same(4, $yevgeni->Combat, 'Combat');
                Assert::same(2, $yevgeni->Finesse, 'Finesse');
                Assert::same(1, $yevgeni->Influence, 'Influence');
                Assert::same(5, $yevgeni->CrewCap, 'CrewCap');
                Assert::same(5, $yevgeni->Panache, 'Panache');
                Assert::true($yevgeni->hasTrait('Leader'), 'Leader');
                Assert::true($yevgeni->hasTrait('Exile'), 'Exile');
                Assert::true($yevgeni->hasTrait('Hero'), 'Hero');
                Assert::true($yevgeni->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($yevgeni->hasTrait('Ussura'), 'Ussura trait');
                Assert::true($yevgeni->hasFaction('Ussura'), 'Ussura faction');
                Assert::count(2, $yevgeni->getReactions(), 'two reactions');
                Assert::instanceOf(Reaction_01116a::class, $yevgeni->getReactions()[0], '01116a');
                Assert::instanceOf(Reaction_01116b::class, $yevgeni->getReactions()[1], '01116b');
            },

            'reaction ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $yevgeni = $world->placeCharacter(new _01116(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($yevgeni->Id . '_Reaction_01116a', $yevgeni->getReactions()[0]->Id, 'a');
                Assert::same($yevgeni->Id . '_Reaction_01116b', $yevgeni->getReactions()[1]->Id, 'b');
            },

            // WHY: printed passive — when Yevgeni plays a combat card it gains +1 Thrust.
            'combat card stats: +1 Thrust when Yevgeni is the actor' => function () {
                $world = new TestWorld();
                $yevgeni = $world->placeCharacter(new _01116(), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->calcStats($world, $yevgeni->Id);
                $before = $event->thrust;
                $yevgeni->handleEvent($event);

                Assert::same($before + 1, $event->thrust, '+1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
                Assert::contains($yevgeni->getInjectCode(), $event->explanations[0], 'names Yevgeni');
            },

            'combat card stats: no Thrust bonus when another character is the actor' => function () {
                $world = new TestWorld();
                $yevgeni = $world->placeCharacter(new _01116(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);

                $event = $this->calcStats($world, $other->Id);
                $yevgeni->handleEvent($event);

                Assert::same(0, $event->thrust, 'no bonus');
                Assert::count(0, $event->explanations, 'no explanation');
            },
        ];
    }
}
