<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd04;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd04;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd04_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd04';
    }

    /**
     * @return array{0:_04cd04,1:Action_04cd04}
     */
    private function ready(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $astrid = $world->placeCharacter(new _04cd04(), $location, 1);
        $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_04cd04 $action */
        $action = $astrid->getActions()[0];
        return [$astrid, $action];
    }

    public function tests(): array
    {
        return [
            // WHY: Printed Action, so an uncontrolled city mercenary is not offered.
            // Destinations are adjacent, currently controlled, and allowed to fall.
            // Her own claim is still a destination. Home is a legal origin.
            'available from a controlled unengaged Astrid beside a controlled location' => function () {
                $world = new TestWorld();
                [$astrid, $action] = $this->ready($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not her controller');

                $astrid->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $astrid->Engaged = false;

                $astrid->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
                $astrid->ControllerId = 1;

                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'neighbor uncontrolled');
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'her own claim counts');

                $world->theah->setLocationCanBecomeUncontrolled(Game::LOCATION_CITY_FORUM, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot fall');

                $astrid->Location = Game::LOCATION_PLAYER_HOME;
                $world->theah->setLocationCanBecomeUncontrolled(Game::LOCATION_CITY_FORUM, true);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home origin');
            },

            'trigger opens the choice for the triggering player' => function () {
                $world = new TestWorld();
                [$astrid, $action] = $this->ready($world);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04cd04', $transition->transition, 'state');
                Assert::same(1, $transition->playerId, 'triggering player');
                Assert::same($astrid->Id, $transition->sourceId, 'astrid');
            },

            'args list controlled adjacent city locations' => function () {
                $world = new TestWorld();
                [$astrid, $action] = $this->ready($world);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD04, '');
                Assert::same($astrid->Id, $args['performerId'], 'performer');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum');
            },

            'an ineligible location is rejected before she engages' => function () {
                $world = new TestWorld();
                [, $action] = $this->ready($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD04, '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not a destination');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },

            // WHY: Engage is the cost. The move does not engage her again.
            'a legal location engages her, drops control, and moves her there' => function () {
                $world = new TestWorld();
                [$astrid, $action] = $this->ready($world);
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD04, '', [Game::LOCATION_CITY_FORUM]);

                $engaged = $world->theah->queuedOfType(EventCardEngaged::class)[0];
                Assert::same($astrid->Id, $engaged->cardId, 'she engages');
                $drop = $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $drop->location, 'forum');
                Assert::same(1, $drop->playerId, 'her controller');
                $moving = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $moving->toLocation, 'destination');
                Assert::false($moving->engage, 'move does not engage');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'resolved');
                Assert::same('locationChosen', $world->game->gamestate->transitions[0], 'transition');
            },
        ];
    }
}
