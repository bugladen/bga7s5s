<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03054;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03054_Test extends TestCase
{
    public function name(): string
    {
        return '_03054 No Steel, No Surrender';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran brawl scheme' => function () {
                $card = new _03054();
                Assert::instanceOf(Scheme::class, $card, 'scheme');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(5, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Kulachniy Boi'), 'Kulachniy Boi');
                Assert::true($card->hasTrait('Brawl'), 'Brawl');
                Assert::instanceOf(Action_03054::class, $card->getActions()[0], 'action');
            },

            'resolve adds one renown to the docks and the forum' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03054(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $locations = array_map(fn ($e) => $e->location, $world->theah->queuedOfType(EventRenownAddedToLocation::class));
                Assert::same([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM], $locations, 'docks and forum');
            },
        ];
    }
}
