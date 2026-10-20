<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03006_Test extends TestCase
{
    public function name(): string
    {
        return '_03006 Premonition';
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce scheme with a Strega Reaction' => function () {
                $scheme = new _03006();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(33, $scheme->Initiative, 'initiative');
                Assert::same(1, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($scheme->hasTrait('Sorte'), 'Sorte');
                Assert::true($scheme->hasTrait('Weave'), 'Weave');
                Assert::instanceOf(Reaction_03006::class, $scheme->getReactions()[0], 'reaction');
            },

            'resolve only opens the two-location chooser' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03006(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('03006', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'another card resolving does nothing' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03006(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $event->playerId = 1;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
