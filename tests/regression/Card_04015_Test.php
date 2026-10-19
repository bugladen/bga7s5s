<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04015_Test extends TestCase
{
    public function name(): string
    {
        return '_04015 Through Thick and Thin';
    }

    public function tests(): array
    {
        return [
            'is a duty scheme that asks for two renown locations' => function () {
                $card = new _04015();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(4, $card->Initiative, 'initiative');
                Assert::same(1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::count(1, $card->getActions(), 'action');

                $world = new TestWorld();
                $scheme = $world->placeCard($card, Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Player One';
                $world->fireOn($scheme, $resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04015', $transition->transition, 'two locations');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'medium');
            },
        ];
    }
}
