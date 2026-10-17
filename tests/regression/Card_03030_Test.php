<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03030;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03030_Test extends TestCase
{
    public function name(): string
    {
        return '_03030 Sworn Swords';
    }

    public function tests(): array
    {
        return [
            'constructs a Montaigne challenge scheme' => function () {
                $scheme = new _03030();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(36, $scheme->Initiative, 'initiative');
                Assert::true($scheme->hasTrait('Oathsworn'), 'Oathsworn');
                Assert::true($scheme->hasTrait('Challenge'), 'Challenge');
                Assert::instanceOf(Action_03030::class, $scheme->getActions()[0], 'action');
            },

            // WHY: Resolve does not place Renown. It opens the location picker at medium
            // priority and names the resolving player.
            'resolve opens the location picker' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03030(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->theah = $world->theah;
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Ada';
                $scheme->handleEvent($resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03030', $transition->transition, 'picker');
                Assert::same($scheme->Id, $transition->sourceId, 'scheme');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'medium');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no renown yet');
            },
        ];
    }
}
