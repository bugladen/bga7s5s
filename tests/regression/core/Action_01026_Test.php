<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01026_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01026';
    }

    public function tests(): array
    {
        return [
            'available with Red Hand performer facing opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Red Hand or without opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Red Hand');

                $world2 = new TestWorld();
                $risk2 = $world2->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world2->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                /** @var Action_01026 $action2 */
                $action2 = $risk2->getActions()[0];
                Assert::false($action2->isAvailableToPlayer(1, $world2->theah), 'no foe');
            },

            'trigger queues transition 01026' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01026', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'isValidTargetForAbility requires opposing character at performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe ok');
                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally rejected');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'wrong loc');
            },

            'act destroys performer and engages en garde target' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01026,
                    'highDramaPhase01026',
                    $foe->Id
                );

                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act sends already-engaged target Home instead of engaging' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01026,
                    'highDramaPhase01026',
                    $foe->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move home');
                Assert::same($foe->Id, $moves[0]->cardId, 'target');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'home');
            },
        ];
    }
}
