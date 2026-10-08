<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_01062_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01062';
    }

    public function tests(): array
    {
        return [
            'available when own Duelist is adjacent' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent Duelist');
            },

            // WHY: getAdjacentCityLocations defaults includeHome=true, so a Duelist at Home counts as adjacent.
            'available when own Duelist is at Home' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Home Duelist', ['Duelist']), Game::LOCATION_PLAYER_HOME, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home adjacent');
            },

            'unavailable without a Duelist adjacent' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Not Duelist'), Game::LOCATION_CITY_FORUM, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Duelist');
            },

            'unavailable when only an opposing Duelist is adjacent' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'opposing');
            },

            'unavailable when Duelist is non-adjacent' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                // 2p: Docks adjacent only to Forum (and Home)
                $world->placeCharacter(new GenericCharacter('Far Duelist', ['Duelist']), Game::LOCATION_CITY_BAZAAR, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Bazaar not adjacent to Docks');
            },

            'unavailable when Odette is not in the city' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'at Home');
            },

            // WHY: Fate's Silence blanks Leader printed Actions (CardAction gate).
            'unavailable when Odette is blanked' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);
                $odette->addCondition(Game::FATES_SILENCE_CONDITION);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'getPerformersForAction lists only own adjacent Duelists' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $near = $world->placeCharacter(new GenericCharacter('Near', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);
                $nonDuelist = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Duelist']), Game::LOCATION_CITY_FORUM, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far', ['Duelist']), Game::LOCATION_CITY_BAZAAR, 1);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));

                Assert::same([$near->Id], $ids, 'only near own Duelist');
                Assert::false(in_array($nonDuelist->Id, $ids, true), 'non-Duelist excluded');
                Assert::false(in_array($foe->Id, $ids, true), 'foe excluded');
                Assert::false(in_array($far->Id, $ids, true), 'far excluded');
            },

            'trigger moves chosen Duelist to Odette without engaging and resolves' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move queued');
                Assert::same($duelist->Id, $moves[0]->cardId, 'Duelist moves');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to Odette');
                Assert::false($moves[0]->engage, 'does not engage');
                Assert::same($odette->Id, $moves[0]->sourceId, 'source Odette');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'ignores trigger for a different action id' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);

                /** @var Action_01062 $action */
                $action = $odette->getActions()[0];
                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no events');
            },
        ];
    }
}
