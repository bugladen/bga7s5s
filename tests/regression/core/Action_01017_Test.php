<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01017_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01017';
    }

    public function tests(): array
    {
        return [
            'available in city with another character at location' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable alone in city or at Home' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');

                $alcee->Location = Game::LOCATION_PLAYER_HOME;
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_PLAYER_HOME, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'at Home');
            },

            'trigger queues transition 01017' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01017', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act destroys Alcee and engages target' => function () {
                $world = new TestWorld();
                $alcee = $world->placeCharacter(new _01017(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01017 $action */
                $action = $alcee->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01017,
                    'highDramaPhase01017',
                    $foe->Id
                );

                Assert::same($alcee->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
