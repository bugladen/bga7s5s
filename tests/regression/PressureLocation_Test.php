<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;

class PressureLocation_Test extends TestCase
{
    public function name(): string
    {
        return 'PressureLocation';
    }

    public function tests(): array
    {
        return [
            'basic claim keeps the city site after the performer is discarded' => function () {
                $world = new TestWorld();
                $stefano = $world->placeCharacter(new GenericCharacter('Stefano'), 'Discard-7', 1);
                $world->game->discardOrLockerByCardId[$stefano->Id] = true;

                $location = $world->theah->resolvePressureLocation(
                    $stefano,
                    true,
                    Game::LOCATION_CITY_BAZAAR
                );

                Assert::same(Game::LOCATION_CITY_BAZAAR, $location, 'claim site');
            },

            'basic claim uses the performer location while they are still in the city' => function () {
                $world = new TestWorld();
                $stefano = $world->placeCharacter(new GenericCharacter('Stefano'), Game::LOCATION_CITY_BAZAAR, 1);

                $location = $world->theah->resolvePressureLocation(
                    $stefano,
                    true,
                    Game::LOCATION_CITY_DOCKS
                );

                Assert::same(Game::LOCATION_CITY_BAZAAR, $location, 'still at bazaar');
            },

            'non-basic pressure follows a discarded performer' => function () {
                $world = new TestWorld();
                $stefano = $world->placeCharacter(new GenericCharacter('Stefano'), 'Discard-7', 1);
                $world->game->discardOrLockerByCardId[$stefano->Id] = true;

                $location = $world->theah->resolvePressureLocation(
                    $stefano,
                    false,
                    Game::LOCATION_CITY_BAZAAR
                );

                Assert::same('Discard-7', $location, 'unchanged for other pressure');
            },

            'no performer uses the captured location' => function () {
                $world = new TestWorld();

                $location = $world->theah->resolvePressureLocation(
                    null,
                    true,
                    Game::LOCATION_CITY_FORUM
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $location, 'captured');
            },
        ];
    }
}
