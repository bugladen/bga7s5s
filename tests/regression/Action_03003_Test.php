<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03003_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03003';
    }

    public function tests(): array
    {
        return [
            'available when own Thug faces foe at Don location' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: no Engage printed — already-engaged Thugs remain eligible
            'available when Thug is already engaged' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $thug->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged Thug ok');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_PLAYER_HOME, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'trigger queues transition 03003' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('03003', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY regression: prior code conditionally engaged unengaged Thugs even
            // though print has no Engage cost. Challenge type keeps Thug off
            // stIssueChallenge auto-engage; action must emit no engage either.
            'target pick issues Combat challenge without engaging Thug' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $thug->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_03003_2,
                    'highDramaPhase03003_2',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(
                    Game::DON_CONSTANZO_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'type off auto-engage'
                );
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::same('03003_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },
        ];
    }
}
