<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03029_Test extends TestCase
{
    public function name(): string
    {
        return '_03029 Hour of Blood';
    }

    public function tests(): array
    {
        return [
            'constructs a Montaigne Sorcery scheme' => function () {
                $scheme = new _03029();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(71, $scheme->Initiative, 'initiative');
                Assert::same(0, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($scheme->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($scheme->hasTrait('Porté'), 'Porté');
                Assert::instanceOf(Action_03029::class, $scheme->getActions()[0], 'action');
            },

            // WHY: Resolve adds one Renown to the Forum and one to the Docks. It does not
            // move Renown. A different scheme id is ignored.
            'resolve adds Renown to the Forum and the Docks' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03029(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->theah = $world->theah;
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Ada';
                $scheme->handleEvent($resolve);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[1]->location, 'docks');
                Assert::same(1, $added[0]->amount, 'one');
                Assert::false($added[0]->isMove, 'not a move');

                $other = $world->placeCard(new _03029(), Game::LOCATION_PLAYER_HOME, 2);
                $world->theah->takeQueuedEvents();
                $resolve->scheme = $other;
                $scheme->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedEvents, 'not this scheme');
            },
        ];
    }
}
