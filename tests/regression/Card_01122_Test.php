<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01122;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01122;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_01122_Test extends TestCase
{
    public function name(): string
    {
        return '_01122 Torsten Vakt';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Scoundrel with Reaction_01122' => function () {
                $torsten = new _01122();
                Assert::instanceOf(Character::class, $torsten, 'Character');
                Assert::instanceOf(IHasReactions::class, $torsten, 'reactions');
                Assert::same(6, $torsten->Resolve, 'Resolve');
                Assert::same(3, $torsten->Combat, 'Combat');
                Assert::same(1, $torsten->Finesse, 'Finesse');
                Assert::same(2, $torsten->Influence, 'Influence');
                Assert::true($torsten->hasFaction('Ussura'), 'Ussura');
                Assert::true($torsten->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($torsten->hasTrait('Murskaaja'), 'Murskaaja');
                Assert::true($torsten->hasTrait('Vesten'), 'Vesten');
                Assert::instanceOf(Reaction_01122::class, $torsten->getReactions()[0], 'Reaction_01122');
            },

            'combat cards gain +1 Thrust when Torsten has 2+ wounds' => function () {
                $world = new TestWorld();
                $torsten = $world->placeCharacter(new _01122(), Game::LOCATION_CITY_DOCKS, 1);
                $torsten->Wounds = 2;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $torsten->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $world->fireOn($torsten, $event);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
            },

            'combat cards gain nothing with fewer than 2 wounds' => function () {
                $world = new TestWorld();
                $torsten = $world->placeCharacter(new _01122(), Game::LOCATION_CITY_DOCKS, 1);
                $torsten->Wounds = 1;

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $torsten->Id;
                $event->theah = $world->theah;
                $world->fireOn($torsten, $event);

                Assert::same(0, $event->thrust, 'no bonus');
            },

            'adversary combat cards are not boosted by Torsten\'s wounds' => function () {
                $world = new TestWorld();
                $torsten = $world->placeCharacter(new _01122(), Game::LOCATION_CITY_DOCKS, 1);
                $torsten->Wounds = 3;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->adversaryId = $torsten->Id;
                $event->theah = $world->theah;
                $world->fireOn($torsten, $event);

                Assert::same(0, $event->thrust, 'not our combat card');
            },
        ];
    }
}
