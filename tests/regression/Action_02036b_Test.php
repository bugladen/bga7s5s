<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_02036b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02036b';
    }

    /**
     * @return array{0:_02036,1:Action_02036b,2:GenericCharacter}
     */
    private function scene(TestWorld $world, string $location = Game::LOCATION_CITY_FORUM): array
    {
        $scheme = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 1);
        $pirate = $world->placeCharacter(new GenericCharacter('Pirate', ['Pirate']), $location, 1);
        /** @var Action_02036b $action */
        $action = $scheme->getActions()[1];
        return [$scheme, $action, $pirate];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction that requires a performer' => function () {
                $action = new Action_02036b();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // 2-player map: Forum is adjacent to City Docks. Bazaar is not.
            'available to a Pirate adjacent to City Docks' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'forum');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::same($pirate->Id, $performers[0]->Id, 'pirate');
            },

            'unavailable at the docks, not adjacent, or without the Pirate trait' => function () {
                $world = new TestWorld();
                [$scheme, $action, $pirate] = $this->scene($world, Game::LOCATION_CITY_DOCKS);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already docks');

                $pirate->Location = Game::LOCATION_CITY_BAZAAR;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'bazaar');

                $pirate->Location = Game::LOCATION_CITY_FORUM;
                $pirate->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not pirate');

                $pirate->ModifiedTraits = ['Pirate'];
                $scheme->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not home');
            },

            // WHY: Adjacency is not symmetric. Docks list Ole's Inn only at 3+ players,
            // but Ole's Inn always lists the docks. This action asks from the Pirate's location.
            'Pirate at Ole Inn can move to the docks even in a 2-player game' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world, Game::LOCATION_CITY_OLES_INN);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'oles lists docks');
                Assert::same($pirate->Id, $action->getPerformersForAction(1, $world->theah)[0]->Id, 'performer');
            },

            'trigger moves the performer to City Docks without engaging' => function () {
                $world = new TestWorld();
                [$scheme, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($pirate->Id, $moves[0]->cardId, 'pirate');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'docks');
                Assert::false($moves[0]->engage, 'not engaged');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'does nothing when the chosen performer is missing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, 0);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
