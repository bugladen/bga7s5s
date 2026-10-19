<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03062;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03062_Test extends TestCase
{
    public function name(): string
    {
        return '_03062 Deal with the Devil';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral villainous scheme' => function () {
                $card = new _03062();
                Assert::instanceOf(Scheme::class, $card, 'scheme');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(7, $card->Initiative, 'initiative');
                Assert::same(-2, $card->PanacheModifier, 'panache');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Villainous'), 'Villainous');
                Assert::true($card->hasTrait('Pact'), 'Pact');
                Assert::instanceOf(Action_03062::class, $card->getActions()[0], 'action');
            },

            'resolve adds renown to the forum and the docks' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03062(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $locations = array_map(fn ($e) => $e->location, $world->theah->queuedOfType(EventRenownAddedToLocation::class));
                Assert::same([Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS], $locations, 'forum and docks');
            },
        ];
    }
}
