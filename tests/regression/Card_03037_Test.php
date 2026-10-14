<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_03037_Test extends TestCase
{
    public function name(): string
    {
        return '_03037 Sanjay';
    }

    private function calcStats(TestWorld $world, int $actorId, bool $gambled): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->actorId = $actorId;
        $event->gambled = $gambled;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Castille Leader with Action and Reaction' => function () {
                $sanjay = new _03037();
                Assert::instanceOf(Leader::class, $sanjay, 'Leader');
                Assert::instanceOf(IHasActions::class, $sanjay, 'actions');
                Assert::instanceOf(IHasReactions::class, $sanjay, 'reactions');
                Assert::same(8, $sanjay->Resolve, 'Resolve');
                Assert::same(2, $sanjay->Combat, 'Combat');
                Assert::same(3, $sanjay->Finesse, 'Finesse');
                Assert::same(2, $sanjay->Influence, 'Influence');
                Assert::same(5, $sanjay->CrewCap, 'CrewCap');
                Assert::same(6, $sanjay->Panache, 'Panache');
                Assert::true($sanjay->hasFaction('Castille'), 'Castille');
                Assert::true($sanjay->hasTrait('Leader'), 'Leader');
                Assert::true($sanjay->hasTrait('Pirate'), 'Pirate');
                Assert::true($sanjay->hasTrait('Aragosta'), 'Aragosta');
                Assert::count(1, $sanjay->getActions(), 'one Action');
                Assert::instanceOf(Action_03037::class, $sanjay->getActions()[0], 'Action_03037');
                Assert::count(1, $sanjay->getReactions(), 'one Reaction');
                Assert::instanceOf(Reaction_03037::class, $sanjay->getReactions()[0], 'Reaction_03037');
            },

            // WHY: printed passive — only gambled combat cards get +1 Riposte.
            'combat card stats: +1 Riposte when Sanjay gambled this combat card' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->calcStats($world, $sanjay->Id, true);
                $before = $event->riposte;
                $sanjay->handleEvent($event);

                Assert::same($before + 1, $event->riposte, '+1 Riposte');
                Assert::count(1, $event->explanations, 'explained');
                Assert::contains($sanjay->getInjectCode(), $event->explanations[0], 'names Sanjay');
            },

            // WHY: Roll the Bones additive pass sets event->gambled=false — the chosen
            // card is not a combat card; values attach to 01114. Sanjay must not fire.
            'combat card stats: no Riposte when event is not marked gambled' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->calcStats($world, $sanjay->Id, false);
                $sanjay->handleEvent($event);

                Assert::same(0, $event->riposte, 'no bonus');
                Assert::count(0, $event->explanations, 'no explanation');
            },

            'combat card stats: no Riposte when another character is the actor' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);

                $event = $this->calcStats($world, $other->Id, true);
                $sanjay->handleEvent($event);

                Assert::same(0, $event->riposte, 'no bonus');
                Assert::count(0, $event->explanations, 'no explanation');
            },
        ];
    }
}
