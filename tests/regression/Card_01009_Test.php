<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterLostBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class Card_01009_Test extends TestCase
{
    public function name(): string
    {
        return '_01009 Cirilo Naucriparos';
    }

    public function tests(): array
    {
        return [
            'constructs with Action_01009 and traits' => function () {
                $cirilo = new _01009();
                Assert::instanceOf(IHasActions::class, $cirilo, 'has actions');
                Assert::same(4, $cirilo->Resolve, 'Resolve');
                Assert::same(3, $cirilo->Combat, 'Combat');
                Assert::same(1, $cirilo->Finesse, 'Finesse');
                Assert::same(2, $cirilo->Influence, 'Influence');
                Assert::true($cirilo->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($cirilo->hasTrait('Extortionist'), 'Extortionist');
                Assert::true($cirilo->hasTrait('Numa'), 'Numa');
                Assert::instanceOf(Action_01009::class, $cirilo->getActions()[0], 'Action_01009');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $cirilo->getActions()[0], 'targets cards');
            },

            'mustering Cirilo grants Brute to controlled Mercenaries' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $event = new EventCharacterMustered();
                $event->characterId = $cirilo->Id;
                $event->playerId = 1;
                $world->fireOn($cirilo, $event);

                Assert::true($merc->hasTrait('Brute'), 'Merc gains Brute');
            },

            'recruiting a Mercenary while Cirilo controls grants Brute' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('New Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                $event = new EventCharacterRecruited();
                $event->playerId = 1;
                $event->characterId = $merc->Id;
                $world->fireOn($cirilo, $event);

                Assert::true($merc->hasTrait('Brute'), 'recruited Merc gains Brute');
            },

            'destroying Cirilo removes Brute from controlled Mercenaries' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary', 'Brute']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $event = new EventCharacterDestroyed();
                $event->characterId = $cirilo->Id;
                $event->playerId = 1;
                $world->fireOn($cirilo, $event);

                Assert::false($merc->hasTrait('Brute'), 'Brute removed');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterLostBrute::class), 'lost Brute event');
            },

            // WHY: Constanzo can strip Brute at DuskPhaseEnd; Cirilo re-applies at EndOfDay
            'dusk end of day re-applies Brute to Mercenaries missing it' => function () {
                $world = new TestWorld();
                $cirilo = $world->placeCharacter(new _01009(), Game::LOCATION_PLAYER_HOME, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );

                $event = new EventDuskEndOfDay();
                $world->fireOn($cirilo, $event);

                Assert::true($merc->hasTrait('Brute'), 'Brute re-applied after Constanzo strip');
            },
        ];
    }
}
