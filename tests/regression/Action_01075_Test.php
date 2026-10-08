<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01075_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01075';
    }

    /** @return array{0:_01075,1:GenericCharacter} tabard, wearer */
    private function armed(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        /** @var GenericCharacter $wearer */
        $wearer = $world->placeCharacter(new GenericCharacter('Wearer', ['Musketeer']), $location, 1);
        $tabard = $world->placeCard(new _01075(), $location, 1);
        $tabard->AttachedToId = $wearer->Id;
        $wearer->Attachments[] = $tabard->Id;
        return [$tabard, $wearer];
    }

    private function pressureResult(TestWorld $world, Action_01075 $action, bool $success): EventLocationPressureResult
    {
        $result = new EventLocationPressureResult();
        $result->abilityId = $action->Id;
        $result->success = $success;
        $result->theah = $world->theah;
        return $result;
    }

    public function tests(): array
    {
        return [
            'available when wearer is in the city, ready and can pressure with Influence' => function () {
                $world = new TestWorld();
                [$tabard] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when wearer is engaged' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                $wearer->Engaged = true;
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when wearer has dashed Influence' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                $wearer->DashedInfluence = true;
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed');
            },

            'unavailable when wearer is Home' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                $wearer->Location = Game::LOCATION_PLAYER_HOME;
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when Tabard is not attached' => function () {
                $world = new TestWorld();
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'performer is the wearer' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::same($wearer->Id, $performers[0]->Id, 'wearer');
            },

            'trigger engages wearer + Tabard and starts TABARD Influence pressure' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($wearer->Id, $engages[0]->cardId, 'wearer engaged');
                Assert::same($tabard->Id, $engages[0]->sourceId, 'source Tabard');
                Assert::same(Game::TABARD_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'only TABARD flag (stale flag reset)');
                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring player');
                Assert::same($wearer->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::true(in_array(Game::STAT_INFLUENCE, $pressure[0]->pressureTypes, true), 'Influence');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [$tabard] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'success claims the wearer location and resolves' => function () {
                $world = new TestWorld();
                [$tabard, $wearer] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $action->handleEvent($this->pressureResult($world, $action, true));

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'location');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'success at a location that cannot be claimed resolves without claiming' => function () {
                $world = new TestWorld();
                [$tabard] = $this->armed($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $action->handleEvent($this->pressureResult($world, $action, true));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'failure resolves without claiming' => function () {
                $world = new TestWorld();
                [$tabard] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $action->handleEvent($this->pressureResult($world, $action, false));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure result for another ability is ignored' => function () {
                $world = new TestWorld();
                [$tabard] = $this->armed($world);
                /** @var Action_01075 $action */
                $action = $tabard->getActions()[0];

                $result = new EventLocationPressureResult();
                $result->abilityId = 'other';
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // WHY: FakeGame has no UtilitiesTrait; "You succeed even if tied" for TABARD pressure
            // lives in Game::pressureLocation and is source-locked here.
            'pressure resolution lets TABARD pressure win ties' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/UtilitiesTrait.php');
                $tiesWin = (int)strpos($src, '//Ties win');
                Assert::true($tiesWin > 0, 'tie-win branch present');
                $conditionStart = (int)strrpos(substr($src, 0, $tiesWin), 'if (');
                $region = substr($src, $conditionStart, $tiesWin - $conditionStart);
                Assert::contains('Game::TABARD_PRESSURE_TYPE', $region, 'TABARD in tie-win condition');
            },
        ];
    }
}
