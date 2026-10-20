<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_01043_Test extends TestCase
{
    public function name(): string
    {
        return '_01043 Uwe Zimmerman';
    }

    public function tests(): array
    {
        return [
            'constructs Character Hunter Eisen' => function () {
                $uwe = new _01043();
                Assert::same(5, $uwe->Resolve, 'Resolve');
                Assert::same(3, $uwe->Combat, 'Combat');
                Assert::same(2, $uwe->Finesse, 'Finesse');
                Assert::same(1, $uwe->Influence, 'Influence');
                Assert::true($uwe->hasTrait('Hunter'), 'Hunter');
                Assert::true($uwe->hasFaction('Eisen'), 'Eisen');
                Assert::false($uwe->hasTrait('Mercenary'), 'not printed Mercenary');
            },

            // WHY: hasTrait override with queryCard — selection-time Mercenary for Daniella/Philip/01051
            'counts as Mercenary when queried by Daniella Philip or 01051' => function () {
                $uwe = new _01043();
                Assert::true($uwe->hasTrait('Mercenary', new _01036()), 'Daniella');
                Assert::true($uwe->hasTrait('Mercenary', new _01039()), 'Philip');
                Assert::true($uwe->hasTrait('Mercenary', new _01051()), '01051');
                Assert::false($uwe->hasTrait('Mercenary', new GenericCharacter('Other')), 'other query');
            },

            'combat cards gain +1 Thrust vs Sorcerer adversary' => function () {
                $world = new TestWorld();
                $uwe = $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);
                $sorcerer = $world->placeCharacter(
                    new GenericCharacter('Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $uwe->Id;
                $event->adversaryId = $sorcerer->Id;
                $event->theah = $world->theah;
                $uwe->handleEvent($event);

                Assert::same(1, $event->thrust, 'thrust');
                Assert::true(count($event->explanations) > 0, 'explanation');
            },

            'no Thrust bonus vs non-Sorcerer' => function () {
                $world = new TestWorld();
                $uwe = $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $uwe->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $uwe->handleEvent($event);

                Assert::same(0, $event->thrust, 'no thrust');
            },
        ];
    }
}
