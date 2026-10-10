<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01141;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01141;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01141_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01141';
    }

    /**
     * Strong Hands in hand; performer in city who can Combat-pressure.
     *
     * @return array{0:_01141,1:Action_01141,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01141(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Boxer'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01141 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function pressureResult(
        TestWorld $world,
        Action_01141 $action,
        GenericCharacter $performer,
        bool $success
    ): EventLocationPressureResult {
        $result = new EventLocationPressureResult();
        $result->abilityId = $action->Id;
        $result->success = $success;
        $result->playerId = 1;
        $result->performerId = $performer->Id;
        $result->location = $performer->Location;
        $result->theah = $world->theah;
        return $result;
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that requires a performer' => function () {
                $action = new Action_01141();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer required');
            },

            'available when Risk is in hand and a city character can Combat-pressure' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable when the only character is Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01141(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01141 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no city Combat');
            },

            'unavailable when performer has dashed Combat' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->DashedCombat = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed Combat');
            },

            'getPerformersForAction lists only city characters that can Combat-pressure' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $dashed = $world->placeCharacter(new GenericCharacter('Dashed'), Game::LOCATION_CITY_FORUM, 1);
                $dashed->DashedCombat = true;
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'boxer');
                Assert::false(in_array($dashed->Id, $ids, true), 'dashed excluded');
                Assert::false(in_array($home->Id, $ids, true), 'home excluded');
            },

            'trigger starts Combat pressure at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::PRESSURE_STAT), 'Combat');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::same($performer->Id, $pressure[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $pressure[0]->location, 'docks');
                Assert::true(in_array(Game::STAT_COMBAT, $pressure[0]->pressureTypes, true), 'Combat type');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'pressure success claims the location and resolves' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                $action->handleEvent($this->pressureResult($world, $action, $performer, true));

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'location');
                Assert::same($performer->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: claim is a success payoff — pressure still plays when Leshiye/etc blocks claim.
            'success at an unclaimable location resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $action->handleEvent($this->pressureResult($world, $action, $performer, true));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure failure resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                $action->handleEvent($this->pressureResult($world, $action, $performer, false));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure result for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                $result = $this->pressureResult($world, $action, $performer, true);
                $result->abilityId = 'other';
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
