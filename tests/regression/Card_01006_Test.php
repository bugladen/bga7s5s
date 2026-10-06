<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01006_Test extends TestCase
{
    public function name(): string
    {
        return '_01006 Don Constanzo';
    }

    public function tests(): array
    {
        return [
            'constructs as Vodacce Leader with Reaction_01006' => function () {
                $don = new _01006();
                Assert::instanceOf(Leader::class, $don, 'Don is a Leader');
                Assert::instanceOf(IHasReactions::class, $don, 'Don has reactions');
                Assert::same(7, $don->Resolve, 'Resolve');
                Assert::same(2, $don->Combat, 'Combat');
                Assert::same(2, $don->Finesse, 'Finesse');
                Assert::same(3, $don->Influence, 'Influence');
                Assert::same(6, $don->CrewCap, 'CrewCap');
                Assert::same(6, $don->Panache, 'Panache');
                Assert::true($don->hasTrait('Leader'), 'Leader trait');
                Assert::true($don->hasTrait('Villain'), 'Villain trait');
                Assert::true($don->hasTrait('Red Hand'), 'Red Hand trait');
                Assert::true($don->hasTrait('Vodacce'), 'Vodacce trait');
                Assert::true($don->hasFaction('Vodacce'), 'Faction');

                $reactions = $don->getReactions();
                Assert::count(1, $reactions, 'one reaction');
                Assert::instanceOf(Reaction_01006::class, $reactions[0], 'Reaction_01006');
            },

            'setup queues transition 01006 on EventTableSetup' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventTableSetup();
                $world->fireOn($don, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition queued');
                Assert::same('01006', $transitions[0]->transition, 'transition name');
                Assert::same($don->ControllerId, $transitions[0]->playerId, 'player id');
            },

            'pressure with controlled Thug at location sets CONSTANZO pressure flag' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug', 'Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                $event = new EventPressureOccuring();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->playerId = 1;
                $world->fireOn($don, $event);

                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CONSTANZO_PRESSURE_TYPE),
                    'CONSTANZO_PRESSURE_TYPE set'
                );
                Assert::same($don->Id, $world->game->globals->get(Game::CONSTANZO_ID), 'CONSTANZO_ID stored');
            },

            'pressure without Thug at location does not set flag' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Diplomat', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                $event = new EventPressureOccuring();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->playerId = 1;
                $world->fireOn($don, $event);

                Assert::false(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CONSTANZO_PRESSURE_TYPE),
                    'no CONSTANZO pressure without Thug'
                );
            },

            'state constants exist for setup flow' => function () {
                Assert::same(901006, States::SETUP_TABLE_01006, 'SETUP_TABLE_01006');
                Assert::same(9010062, States::SETUP_TABLE_01006_2, 'SETUP_TABLE_01006_2');
            },
        ];
    }
}
