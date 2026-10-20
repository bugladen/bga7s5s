<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01007;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01007_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01007';
    }

    public function tests(): array
    {
        return [
            'available when another controlled location has renown' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01007 $action */
                $action = $aldo->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when only current location has renown' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);

                /** @var Action_01007 $action */
                $action = $aldo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot move from own location');
            },

            'args list controlled renown locations excluding Aldo location' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 5);

                /** @var Action_01007 $action */
                $action = $aldo->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01007, 'highDramaPhase01007');

                Assert::same($aldo->Id, $args['performerId'], 'performer');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'only Forum eligible');
            },

            'trigger queues transition 01007' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01007 $action */
                $action = $aldo->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01007', $transitions[0]->transition, 'transition');
            },

            'act moves one renown from chosen location to Aldo location' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01007 $action */
                $action = $aldo->getActions()[0];
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01007,
                    'highDramaPhase01007',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(1, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'remove queued');
                Assert::count(1, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'add queued');

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0];
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $removed->location, 'remove from Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added->location, 'add to Aldo location');
                Assert::same(1, $removed->amount, 'remove 1');
                Assert::same(1, $added->amount, 'add 1');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },
        ];
    }
}
