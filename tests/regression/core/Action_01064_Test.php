<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01064;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01064_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01064';
    }

    /** @return array{0:_01064,1:Action_01064,2:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card} */
    private function armed(TestWorld $world): array
    {
        $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
        $hand = $world->placeCard(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 1);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01064 $action */
        $action = $guillen->getActions()[0];
        return [$guillen, $action, $hand];
    }

    public function tests(): array
    {
        return [
            'available with a hand card and adjacent Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with an empty hand' => function () {
                $world = new TestWorld();
                [, $action, $hand] = $this->armed($world);
                $hand->Location = Game::LOCATION_CITY_DISCARD;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no cards to discard');
            },

            'unavailable when no adjacent location has Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Renown');
            },

            'unavailable when only Guillen own location has Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not adjacent to self');
            },

            'unavailable when Renown is only at a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 3);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Bazaar not adjacent to Docks');
            },

            'unavailable when Guillen is at Home' => function () {
                $world = new TestWorld();
                [$guillen, $action] = $this->armed($world);
                $guillen->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in city');
            },

            'unavailable when Guillen is blanked' => function () {
                $world = new TestWorld();
                [$guillen, $action] = $this->armed($world);
                $guillen->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01064' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01064', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'discard choice stores the card and goes to cardChosen' => function () {
                $world = new TestWorld();
                [, $action, $hand] = $this->armed($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01064,
                    'highDramaPlayerTurn_01064',
                    $hand->Id
                );

                Assert::same($hand->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'discard choice rejects a card outside the hand' => function () {
                $world = new TestWorld();
                [$guillen, $action] = $this->armed($world);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01064,
                        'highDramaPlayerTurn_01064',
                        $guillen->Id
                    );
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            'args list only adjacent locations that hold Renown (no Home)' => function () {
                $world = new TestWorld();
                [$guillen, $action] = $this->armed($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01064_2,
                    'highDramaPlayerTurn_01064_2'
                );

                Assert::same($guillen->Id, $args['performerId'], 'performer');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'Forum only');
            },

            'location choice discards the card and moves one Renown in a single batch' => function () {
                $world = new TestWorld();
                [, $action, $hand] = $this->armed($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $hand->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01064_2,
                    'highDramaPlayerTurn_01064_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'discarded card');

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->toLocation, 'to Guillen');
                Assert::same(Game::LOCATION_CITY_FORUM, $removed[0]->location, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'added');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'batch remove');
                Assert::same($moving[0]->batchId, $added[0]->batchId, 'batch add');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'location choice rejects a non-adjacent location without discarding' => function () {
                $world = new TestWorld();
                [, $action, $hand] = $this->armed($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $hand->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01064_2,
                        'highDramaPlayerTurn_01064_2',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (\Throwable $e) {
                    $threw = true;
                }

                Assert::true($threw, 'non-adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
