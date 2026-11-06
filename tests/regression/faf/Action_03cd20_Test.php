<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
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
                Assert::false($action->Used, 'global Used cleared');
                Assert::same(
                    [['playerId' => 2, 'playerName' => 'Player Two', 'playerColor' => '000002']],
                    $card->getEarlyMorningArrangementsUsedListData($world->game),
                    'used list'
                );
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

            // WHY: City-event Actions stay in play and are once-per-Day per player. A failed
            // pressure must lock only the acting player — clearing the global Used flag so
            // Player B can still try while the card remains in the city.
            'failed pressure resolves the action and leaves the card' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('AtDocks'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $action->Used = true;

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
                Assert::false($action->Used, 'global Used cleared');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked for A');
                Assert::true($action->isAvailableToPlayer(2, $world->theah), 'still open for B');
                Assert::same(
                    [['playerId' => 1, 'playerName' => 'Player One', 'playerColor' => '000001']],
                    $card->getEarlyMorningArrangementsUsedListData($world->game),
                    'used list'
                );
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

            // WHY: After a successful pressure the card lives in Home. EventCityAction
            // used to treat Home like any other location, so friendly chars at Home
            // made the City Action usable again — including re-pressuring.
            'City Action is unavailable while the card is in Home' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd20(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('HomeBody'), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_03cd20 $action */
                $action = $card->getActions()[0];

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not while in Home');
            },

            'City Action is available when the card is in the city with a friendly character' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('AtDocks'), Game::LOCATION_CITY_DOCKS, 1);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city + friendly');
            },

            'using it again the same day is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('AtDocks'), Game::LOCATION_CITY_DOCKS, 1);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->playerId = 1;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;

                $threw = false;
                try {
                    $action->eventCheck($triggered);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('already used', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'rejected');
            },

            'Dusk clears the per-player lock' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('AtDocks'), Game::LOCATION_CITY_DOCKS, 1);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->playerId = 1;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked');

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $action->handleEvent($dusk);

                Assert::true($card->IsUpdated, 'rewritten');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available again');
                Assert::same([], $card->getEarlyMorningArrangementsUsedListData($world->game), 'list cleared');
            },
        ];
    }
}
