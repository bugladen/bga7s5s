<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02033_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02033';
    }

    private function cityPiece(string $name = 'City Piece'): CityCharacter
    {
        return new class($name) extends CityCharacter {
            public function __construct(string $name)
            {
                parent::__construct();
                $this->Name = $name;
            }
        };
    }

    /**
     * @return array{0:_02033,1:Action_02033,2:CityCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
        $city = $world->placeCharacter($this->cityPiece(), Game::LOCATION_CITY_FORUM, 0);
        /** @var Action_02033 $action */
        $action = $rosa->getActions()[0];
        return [$rosa, $action, $city];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_02033(), 'CharacterAction');
            },

            'state constant registered' => function () {
                Assert::same(402033, States::HIGH_DRAMA_PLAYER_TURN_02033, 'state');
            },

            // Adjacent uncontrolled city cards only. 2-player docks touch Forum, not Bazaar.
            'available when an adjacent uncontrolled City Card exists' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Rosa is not in the city' => function () {
                $world = new TestWorld();
                [$rosa, $action] = $this->scene($world);
                $rosa->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when the adjacent City Card is controlled' => function () {
                $world = new TestWorld();
                [, $action, $city] = $this->scene($world);
                $city->ControllerId = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled');
            },

            'ignores faction characters and non-adjacent city cards' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_FORUM, 0);
                $world->placeCharacter($this->cityPiece('Far'), Game::LOCATION_CITY_BAZAAR, 0);
                /** @var Action_02033 $action */
                $action = $rosa->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'none');
            },

            'unavailable when Used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'EventActionTriggered queues the 02033 chooser' => function () {
                $world = new TestWorld();
                [$rosa, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('02033', $transitions[0]->transition, 'name');
                Assert::same($rosa->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'args list adjacent uncontrolled City Card ids' => function () {
                $world = new TestWorld();
                [$rosa, $action, $city] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $rosa->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02033, 'highDramaPlayerTurn_02033');
                Assert::same($rosa->Id, $args['performerId'], 'performer');
                Assert::same([$city->Id], $args['ids'], 'ids');
            },

            // Move engage=false to Rosa's location, then ActionResolved.
            'choosing a City Card moves it to Rosa without engaging' => function () {
                $world = new TestWorld();
                [$rosa, $action, $city] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $rosa->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02033,
                    'highDramaPlayerTurn_02033',
                    $city->Id
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($city->Id, $moves[0]->cardId, 'card');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'not engaged');
                Assert::same($rosa->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'resolved');
                Assert::same(1, $resolved[0]->playerId, 'player');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'rejects a card that is not an adjacent City Card' => function () {
                $world = new TestWorld();
                [$rosa, $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_FORUM, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $rosa->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02033,
                        'highDramaPlayerTurn_02033',
                        $ally->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
