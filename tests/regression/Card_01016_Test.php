<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01016_Test extends TestCase
{
    public function name(): string
    {
        return '_01016 Plans Within Plans';
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Reaction_01016' => function () {
                $scheme = new _01016();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(73, $scheme->Initiative, 'Initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Cunning'), 'Cunning');
                Assert::true($scheme->hasTrait('Gang'), 'Gang');
                Assert::true($scheme->hasFaction('Vodacce'), 'Faction');
                Assert::instanceOf(Reaction_01016::class, $scheme->getReactions()[0], 'Reaction_01016');
            },

            // WHY: resolve does not add renown itself — transitions to player choose-two-locations
            'resolve queues transition 01016' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01016', $transitions[0]->transition, 'transition name');
                Assert::same(1, $transitions[0]->playerId, 'player');
            },

            'state constants registered' => function () {
                Assert::same(2601016, States::PLANNING_PHASE_RESOLVE_SCHEMES_01016, '01016');
                Assert::same(26010162, States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2, '01016_2');
                Assert::same(26010163, States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3, '01016_3');
            },
        ];
    }
}
