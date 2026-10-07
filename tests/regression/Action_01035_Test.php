<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

final class TestMercenary_01035 extends CityCharacter
{
    public function __construct(bool $negotiable = true)
    {
        parent::__construct();
        $this->Name = 'Test Mercenary';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 1;
        $this->WealthCost = 2;
        $this->Negotiable = $negotiable;
        $this->Traits = ['Mercenary'];
        $this->resetCard();
    }
}

class Action_01035_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01035';
    }

    public function tests(): array
    {
        return [
            'available when Kaspar is unengaged in city' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Kaspar is engaged' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $kaspar->Engaged = true;

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when Kaspar is at Home' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_PLAYER_HOME, 1);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            // WHY regression: audit 2026-04-10 — +2 stacks with ModifiedInfluence when parleying
            'getParleyDiscount adds +2 when Kaspar is the performer' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                $discount = $kaspar->getParleyDiscount($world->theah, $kaspar, true, $explanations);
                // Character base adds ModifiedInfluence (2) + Kaspar bonus (2)
                Assert::same(4, $discount, 'parley discount');
            },

            'getParleyDiscount does not add Kaspar bonus when not parleying' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                $discount = $kaspar->getParleyDiscount($world->theah, $kaspar, false, $explanations);
                Assert::same(0, $discount, 'no parley');
            },

            'trigger engages Kaspar, reveals mercenary, queues city add and 01035' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCard(new TestMercenary_01035(), Game::LOCATION_CITY_DECK, 0);
                $world->game->cityDeckRevealResult = $merc;

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($kaspar->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');
                Assert::same($merc->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::count(1, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'add to loc');
                Assert::same('01035', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'trigger with no mercenary clears CHOSEN_CARD and still transitions' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->cityDeckRevealResult = null;
                $world->game->globals->set(Game::CHOSEN_CARD, 999);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD), 'cleared');
                Assert::same('01035', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'stateFromAction routes found vs notFound' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];

                $world->game->globals->set(Game::CHOSEN_CARD, 5);
                $world->game->globals->set(Game::CURRENT_PLAYER, 1);
                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01035_2,
                    'highDramaPhase01035_2'
                );
                Assert::same(['found'], $world->game->gamestate->transitions, 'found');

                $world->game->gamestate->transitions = [];
                $world->game->globals->delete(Game::CHOSEN_CARD);
                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01035_2,
                    'highDramaPhase01035_2'
                );
                Assert::same(['notFound'], $world->game->gamestate->transitions, 'notFound');
            },

            'pass sinks mercenary and resolves' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCard(new TestMercenary_01035(), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $merc->Id);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01035_3);

                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToCityDeck::class), 'sink');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'recruit negotiable mercenary goes to recruit then parley choice' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCard(new TestMercenary_01035(true), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $merc->Id);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01035_3,
                    'highDramaPhase01035_3',
                    1
                );

                Assert::same(Game::KASPAR_RECRUIT_TYPE, $world->game->globals->get(Game::RECRUIT_TYPE), 'recruit type');
                Assert::same($kaspar->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same(['recruit'], $world->game->gamestate->transitions, 'recruit');
            },

            'recruit non-negotiable skips parley' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCard(new TestMercenary_01035(false), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $merc->Id);

                /** @var Action_01035 $action */
                $action = $kaspar->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01035_3,
                    'highDramaPhase01035_3',
                    1
                );

                Assert::same(['recruitNoParley'], $world->game->gamestate->transitions, 'no parley');
                Assert::same(0, $world->game->globals->get(Game::DISCOUNT), 'discount 0');
            },
        ];
    }
}
