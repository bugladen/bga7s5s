<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04005;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_04005_Test extends TestCase
{
    public function name(): string
    {
        return '_04005 Denounced, Disgraced';
    }

    public function tests(): array
    {
        return [
            'is a villainous scheme that adds docks renown when resolved' => function () {
                $card = new _04005();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(35, $card->Initiative, 'initiative');
                Assert::true($card->hasTrait('Purge'), 'Purge');
                Assert::count(1, $card->getActions(), 'action');

                $world = new TestWorld();
                $scheme = $world->placeCard($card, Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown->location, 'docks');
                Assert::same(1, $renown->amount, 'one');
            },
        ];
    }
}
