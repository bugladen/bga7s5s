<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03017_Test extends TestCase
{
    public function name(): string
    {
        return '_03017 Noble Sacrifice';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Finale scheme' => function () {
                $scheme = new _03017();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(53, $scheme->Initiative, 'initiative');
                Assert::same(0, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
                Assert::true($scheme->hasTrait('Heroic'), 'Heroic');
                Assert::true($scheme->hasTrait('Finale'), 'Finale');
                Assert::instanceOf(Reaction_03017::class, $scheme->getReactions()[0], 'reaction');
            },

            // WHY: Resolve does not place the Renown itself. It opens the two-location picker.
            'resolve opens the location picker and does not add Renown yet' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03017(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03017', $transition->transition, 'picker');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'medium');
                Assert::same($scheme->Id, $transition->sourceId, 'source');
                Assert::same(1, $transition->playerId, 'the resolving player');
            },

            'another scheme resolving does nothing' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03017(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _03018(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
