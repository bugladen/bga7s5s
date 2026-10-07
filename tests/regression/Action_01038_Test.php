<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01038_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01038';
    }

    public function tests(): array
    {
        return [
            'available in city' => function () {
                $world = new TestWorld();
                $otto = $world->placeCharacter(new _01038(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01038 $action */
                $action = $otto->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $otto = $world->placeCharacter(new _01038(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01038 $action */
                $action = $otto->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'trigger queues transition 01038' => function () {
                $world = new TestWorld();
                $otto = $world->placeCharacter(new _01038(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01048(), 'Deck-1', 1);
                $world->game->topFactionCards = [['id' => $att->Id]];
                /** @var Action_01038 $action */
                $action = $otto->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01038', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'choose attachment puts into hand and sinks rest' => function () {
                $world = new TestWorld();
                $otto = $world->placeCharacter(new _01038(), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new _01048(), 'Deck-1', 1);
                $risk = $world->placeCard(new _01026(), 'Deck-1', 1);
                $other = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $att->Id],
                    ['id' => $risk->Id],
                    ['id' => $other->Id],
                ];

                /** @var Action_01038 $action */
                $action = $otto->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01038_3,
                    'highDramaPhase01038_3',
                    $att->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class), 'remove');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'hand');
                Assert::count(2, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'sink rest');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'chosen');
            },

            'pass with no attachments sinks all' => function () {
                $world = new TestWorld();
                $otto = $world->placeCharacter(new _01038(), Game::LOCATION_CITY_DOCKS, 1);
                $a = $world->placeCard(new _01026(), 'Deck-1', 1);
                $b = $world->placeCard(new _01026(), 'Deck-1', 1);
                $c = $world->placeCard(new _01026(), 'Deck-1', 1);
                $world->game->topFactionCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                    ['id' => $c->Id],
                ];

                /** @var Action_01038 $action */
                $action = $otto->getActions()[0];
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01038_3);

                Assert::count(3, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'sink all');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },
        ];
    }
}
