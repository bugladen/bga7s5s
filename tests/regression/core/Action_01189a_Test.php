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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01189;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01189a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01189a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01189a';
    }

    /**
     * Point of Opportunity at Docks; unengaged performer; Forum has Renown.
     *
     * @return array{0:_01189,1:Action_01189a,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $poo = $world->placeCard(new _01189(), Game::LOCATION_CITY_DOCKS, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01189a $action */
        $action = $poo->getActions()[0];
        return [$poo, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is an EventCityAction' => function () {
                Assert::instanceOf(EventCityAction::class, new Action_01189a(), 'EventCityAction');
            },

            'available when an adjacent location has Renown and an unengaged performer is here' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when no adjacent location has Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no renown');
            },

            'unavailable when only engaged characters are at the location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'trigger queues transition 01189a' => function () {
                $world = new TestWorld();
                [$poo, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01189a', $transitions[0]->transition, 'name');
                Assert::same($poo->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY (journal 2026-04-08-13): args only list adjacent locations with Renown > 0.
            'args list only adjacent locations with Renown' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01189a,
                    'highDramaPlayerTurn_01189a'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locations'], 'Forum only');
            },

            // WHY: Engage → moving → remove from adjacent → add here (isMove) → discard → ActionResolved.
            'act engages, moves Renown from adjacent, discards as effect, and resolves' => function () {
                $world = new TestWorld();
                [$poo, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01189a,
                    'highDramaPlayerTurn_01189a',
                    [Game::LOCATION_CITY_FORUM]
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->fromLocation, 'from Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->toLocation, 'to Docks');

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removed, 'remove');
                Assert::same(Game::LOCATION_CITY_FORUM, $removed[0]->location, 'Forum');

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'add');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'Docks');
                Assert::true($added[0]->isMove, 'isMove');

                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'discard');
                Assert::same($poo->Id, $discard[0]->cardId, 'poo');
                Assert::true($discard[0]->asEffect, 'as effect');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCardEngaged::class, $order, true)
                        < array_search(EventRenownMovingBetweenLocations::class, $order, true),
                    'engage before move'
                );
                Assert::true(
                    array_search(EventCardAddedToCityDiscardPile::class, $order, true)
                        < array_search(EventActionResolved::class, $order, true),
                    'discard before resolve'
                );
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'act refuses a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01189a,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'state constant registered' => function () {
                Assert::same(4011891, States::HIGH_DRAMA_PLAYER_TURN_01189a, 'state');
            },
        ];
    }
}
