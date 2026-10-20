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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01024_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01024';
    }

    public function tests(): array
    {
        return [
            'available with Leader in play and Thug in discard' => function () {
                $world = new TestWorld();
                $bravos = $world->placeCard(new _01024(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Discarded Thug', ['Thug', 'Red Hand']),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                /** @var Action_01024 $action */
                $action = $bravos->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Thug in discard' => function () {
                $world = new TestWorld();
                $bravos = $world->placeCard(new _01024(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);

                /** @var Action_01024 $action */
                $action = $bravos->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no thug');
            },

            'unavailable without Leader in play' => function () {
                $world = new TestWorld();
                $bravos = $world->placeCard(new _01024(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Crew', ['Duelist']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Discarded Thug', ['Thug']),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                /** @var Action_01024 $action */
                $action = $bravos->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no leader');
            },

            'trigger queues transition 01024' => function () {
                $world = new TestWorld();
                $bravos = $world->placeCard(new _01024(), Game::LOCATION_HAND, 1);
                /** @var Action_01024 $action */
                $action = $bravos->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01024', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act musters Thug from discard to Leader location' => function () {
                $world = new TestWorld();
                $bravos = $world->placeCard(new _01024(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new _01006(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $thug = $world->placeCharacter(
                    new GenericCharacter('Discarded Thug', ['Thug', 'Red Hand']),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                /** @var Action_01024 $action */
                $action = $bravos->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01024,
                    'highDramaPhase01024',
                    $thug->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed');
                $musters = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::count(1, $musters, 'muster');
                Assert::same($thug->Id, $musters[0]->characterId, 'thug');
                Assert::same(Game::LOCATION_CITY_DOCKS, $musters[0]->location, 'at leader loc');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
