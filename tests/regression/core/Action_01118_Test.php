<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01118_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01118';
    }

    /** @return array{0:_01118,1:Action_01118} */
    private function scene(TestWorld $world, string $at = Game::LOCATION_CITY_DOCKS): array
    {
        $elina = $world->placeCharacter(new _01118(), $at, 1);
        /** @var Action_01118 $action */
        $action = $elina->getActions()[0];
        return [$elina, $action];
    }

    public function tests(): array
    {
        return [
            'available in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_PLAYER_HOME);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'unavailable when Fate\'s Silence blanks Elina' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);
                $elina->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01118' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01118', $transitions[0]->transition, 'name');
                Assert::same($elina->Id, $transitions[0]->sourceId, 'source');
            },

            'args list adjacent city locations only (no Home)' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01118, 'x');

                Assert::same($elina->Id, $args['performerId'], 'performer');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'no Home');
            },

            'move to an empty adjacent location does not en garde' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);
                $elina->Engaged = true;

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01118,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($elina->Id, $moves[0]->cardId, 'Elina');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'Forum');
                Assert::false($moves[0]->engage, 'ability move does not engage');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'unopposed');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            // WHY (production comment): en garde only if opposed *and* already Engaged — not when she arrives en garde.
            'opposed + already engaged: queues EventCardEngarded (en garde)' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);
                $elina->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01118,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($elina->Id, $engardes[0]->cardId, 'Elina');
                Assert::same($action->Id, $engardes[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'opposed but not engaged: does not en garde' => function () {
                $world = new TestWorld();
                [$elina, $action] = $this->scene($world);
                $elina->Engaged = false;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01118,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'still en garde');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'refuses a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01118,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
