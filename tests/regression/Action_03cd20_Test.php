<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03cd20_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03cd20';
    }

    /**
     * @return array{0:_03cd20,1:Action_03cd20,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $card = $world->placeCard(new _03cd20(), Game::LOCATION_CITY_DOCKS, 0);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_FORUM, 1);
        /** @var Action_03cd20 $action */
        $action = $card->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$card, $action, $performer];
    }

    public function tests(): array
    {
        return [
            // WHY: Pressure uses the performer's location, not the event's. The stat is Finesse.
            'trigger starts a Finesse pressure and opens pressure resolution' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->scene($world);
                Assert::true($action->RequiresPerformerSelected, 'needs a performer');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring player');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal');
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::PRESSURE_STAT), 'finesse');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::same(1, $pressure[0]->playerId, 'player');
                Assert::same($performer->Id, $pressure[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $pressure[0]->location, 'performer location');
                Assert::true(in_array(Game::STAT_FINESSE, $pressure[0]->pressureTypes, true), 'finesse type');

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('pressureLocation', $transition[0]->transition, 'pressure state');
                Assert::same($card->Id, $transition[0]->sourceId, 'card');
                Assert::same($action->Id, $transition[0]->internalId, 'action');
            },

            // WHY: Success adds the top city card to the event's location, then moves this
            // card Home. ControllerId is written before the move so the client anchor is right.
            'successful pressure adds a city card and sends this card Home' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);
                $top = $world->placeCard(new _03cd20(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $top->Id]];

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->playerId = 2;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                $added = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(1, $added, 'city card');
                Assert::same($top->Id, $added[0]->cardId, 'top');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'event location');

                Assert::same(2, $card->ControllerId, 'controller written first');
                Assert::true($card->IsUpdated, 'updated');
                $move = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $move, 'home');
                Assert::same($card->Id, $move[0]->cardId, 'this card');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move[0]->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_PLAYER_HOME, $move[0]->toLocation, 'home');
                Assert::false($move[0]->engage, 'does not engage');
                Assert::same(2, $move[0]->initiatingPlayerId, 'pressuring player');
                Assert::same($action->Id, $move[0]->abilityId, 'ability');

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::same(2, $resolved[0]->playerId, 'resolved');
            },

            'successful pressure with an empty City Deck still goes Home' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->playerId = 1;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'no top card');
                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'still goes home');
                Assert::same(1, $card->ControllerId, 'controller');
            },

            'failed pressure resolves the action and leaves the card' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->playerId = 1;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'stays');
                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'no card');
                Assert::same(0, $card->ControllerId, 'still uncontrolled');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'a pressure result for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = 'other';
                $result->success = true;
                $result->playerId = 1;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },
        ];
    }
}
