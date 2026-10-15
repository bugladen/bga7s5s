<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_02023_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02023';
    }

    /**
     * @return array{0:_02023,1:Technique_02023,2:GenericCharacter}
     */
    private function duel(TestWorld $world, array $foeTraits = ['Thug']): array
    {
        $jack = $world->placeCharacter(new _02023(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe', $foeTraits), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $jack;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02023 $technique */
        $technique = $jack->getTechniques()[0];
        return [$jack, $technique, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02023(), 'Technique');
            },

            // WHY: duel-only; adversary Thug OR Mercenary.
            'available in duel vs Thug' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, ['Thug']);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'Thug');
            },

            'available in duel vs Mercenary' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, ['Mercenary']);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'Mercenary');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable vs non-Thug non-Mercenary' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, []);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'plain');
            },

            'duel calc adds +1 Thrust' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->thrust = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->thrust, 'thrust');
                Assert::count(1, $event->explanations, 'explained');
            },
        ];
    }
}
