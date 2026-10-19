<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03053_Test extends TestCase
{
    public function name(): string
    {
        return '_03053 Curry Favor';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran trade scheme' => function () {
                $card = new _03053();
                Assert::instanceOf(Scheme::class, $card, 'scheme');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(49, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Trade'), 'Trade');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::instanceOf(Action_03053::class, $card->getActions()[0], 'action');
            },

            'resolve opens a two-location renown picker at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03053(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Player One';
                $world->fireOn($scheme, $resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03053', $transition->transition, 'picker');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'medium');
            },
        ];
    }
}
