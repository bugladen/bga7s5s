<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01117_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01117';
    }

    /** @return array{0:_01117,1:Action_01117} */
    private function scene(TestWorld $world, string $at = Game::LOCATION_CITY_DOCKS, int $renown = 1): array
    {
        $eka = $world->placeCharacter(new _01117(), $at, 1);
        $world->theah->setLocationRenown($at === Game::LOCATION_PLAYER_HOME ? Game::LOCATION_CITY_DOCKS : $at, $renown);
        if ($at !== Game::LOCATION_PLAYER_HOME) {
            $world->theah->setLocationRenown($at, $renown);
        }
        /** @var Action_01117 $action */
        $action = $eka->getActions()[0];
        return [$eka, $action];
    }

    public function tests(): array
    {
        return [
            'available in city when the location has Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_PLAYER_HOME);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when the location has no Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no renown');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'unavailable when Fate\'s Silence blanks Ekaterina' => function () {
                $world = new TestWorld();
                [$eka, $action] = $this->scene($world);
                $eka->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01117' => function () {
                $world = new TestWorld();
                [$eka, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01117', $transitions[0]->transition, 'name');
                Assert::same($eka->Id, $transitions[0]->sourceId, 'source');
            },

            'step 1 args list every other city location' => function () {
                $world = new TestWorld();
                [$eka, $action] = $this->scene($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01117, 'x');

                Assert::same($eka->Id, $args['performerId'], 'performer');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'own excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::count(4, $args['locationIds'], '4 other city locations');
            },

            'step 1 stores CHOSEN_LOCATION and goes to locationChosen' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01117,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_LOCATION), 'renown dest');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            'step 1 refuses Ekaterina\'s own location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01117,
                        'x',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'own location');
            },

            // WHY: printed text — move Renown to location A, then Ekaterina to a *different* location B.
            'step 2 moves Renown then Ekaterina to a third location and resolves' => function () {
                $world = new TestWorld();
                [$eka, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01117_2,
                    'x',
                    [Game::LOCATION_CITY_BAZAAR]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->fromLocation, 'from Docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->toLocation, 'to Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'removed');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'added');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'batch');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'Ekaterina moves');
                Assert::same($eka->Id, $moves[0]->cardId, 'her');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'to Bazaar');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            'step 2 refuses the Renown destination for Ekaterina\'s move' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01117_2,
                        'x',
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same as renown dest');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
