<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03042_Test extends TestCase
{
    public function name(): string
    {
        return '_03042 When Least Expected';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille ambush scheme' => function () {
                $scheme = new _03042();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(66, $scheme->Initiative, 'initiative');
                Assert::same(0, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Ambush'), 'Ambush');
                Assert::true($scheme->hasTrait('Cunning'), 'Cunning');
                Assert::instanceOf(Action_03042::class, $scheme->getActions()[0], 'action');
            },

            'resolve adds Renown to the Docks and the Forum' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03042(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->theah = $world->theah;
                $resolve->scheme = $scheme;
                $scheme->handleEvent($resolve);
                $locations = array_map(
                    fn ($event) => $event->location,
                    $world->theah->queuedOfType(EventRenownAddedToLocation::class)
                );
                Assert::same([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM], $locations, 'docks and forum');
            },
        ];
    }
}
