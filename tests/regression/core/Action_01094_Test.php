<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01094;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01094;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class Action_01094_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01094';
    }

    /** @return array{0:_01094,1:Action_01094} Engaged Anibal at the Docks, which has no Renown. */
    private function scene(TestWorld $world): array
    {
        $anibal = $world->placeCharacter(new _01094(), Game::LOCATION_CITY_DOCKS, 1);
        $anibal->Engaged = true;
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
        /** @var Action_01094 $action */
        $action = $anibal->getActions()[0];
        return [$anibal, $action];
    }

    public function tests(): array
    {
        return [
            'available when engaged at a location with no Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: nothing to "en garde" if he already is.
            'unavailable when already en garde' => function () {
                $world = new TestWorld();
                [$anibal, $action] = $this->scene($world);
                $anibal->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not engaged');
            },

            'unavailable when the location has any Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'one Renown');
            },

            'checks the Renown of his own location only' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 6);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'other location busy, his is quiet');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'his is busy');
            },

            // WHY: cardInCity gate - an Engaged character at Home would make getCityLocation() throw.
            'unavailable (without throwing) when engaged at Home' => function () {
                $world = new TestWorld();
                [$anibal, $action] = $this->scene($world);
                $anibal->Location = Game::LOCATION_PLAYER_HOME;

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Anibal' => function () {
                $world = new TestWorld();
                [$anibal, $action] = $this->scene($world);
                $anibal->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger en gardes Anibal himself and resolves the action' => function () {
                $world = new TestWorld();
                [$anibal, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engarde, 'one en garde');
                Assert::same($anibal->Id, $engarde[0]->cardId, 'target is Anibal');
                Assert::same($anibal->Id, $engarde[0]->sourceId, 'source is Anibal');
                Assert::same($action->Id, $engarde[0]->abilityId, 'ability id');
                Assert::same(1, $engarde[0]->playerId, 'controller');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::instanceOf(EventCardEngarded::class, $world->theah->queuedEvents[0], 'en garde before resolve');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
