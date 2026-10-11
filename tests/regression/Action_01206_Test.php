<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01206;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01206;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01206_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01206';
    }

    /**
     * Coat equipped on city host who can Influence-pressure.
     *
     * @return array{0:_01206,1:Action_01206,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Captain'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01206 $coat */
        $coat = $world->placeCard(new _01206(), Game::LOCATION_CITY_DOCKS, 1);
        $coat->AttachedToId = $host->Id;
        $coat->Engaged = false;
        $host->Attachments[] = $coat->Id;
        /** @var Action_01206 $action */
        $action = $coat->getActions()[0];
        return [$coat, $action, $host];
    }

    private function pressureResult(
        TestWorld $world,
        Action_01206 $action,
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
            'is an AttachmentAction' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_01206(), 'AttachmentAction');
            },

            'available when coat is en garde on a city host who can Influence-pressure' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the coat is engaged' => function () {
                $world = new TestWorld();
                [$coat, $action] = $this->scene($world);
                $coat->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged coat');
            },

            'unavailable when the host is at Home' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when the host has dashed Influence' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $host->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed Influence');
            },

            'unavailable when the coat is not attached' => function () {
                $world = new TestWorld();
                [$coat, $action] = $this->scene($world);
                $coat->AttachedToId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'trigger engages the coat and starts Influence pressure' => function () {
                $world = new TestWorld();
                [$coat, $action, $host] = $this->scene($world);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::same($host->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                // WHY: reset to NORMAL then OR CAPTAINS_COAT (bit 2) — final value is 2, not 0.
                Assert::same(Game::CAPTAINS_COAT_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'coat type');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CAPTAINS_COAT_PRESSURE_TYPE),
                    'Captain\'s Coat flag'
                );

                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engage, 'engage coat');
                Assert::same($coat->Id, $engage[0]->cardId, 'coat');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::same($host->Id, $pressure[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $pressure[0]->location, 'docks');
                Assert::true(in_array(Game::STAT_INFLUENCE, $pressure[0]->pressureTypes, true), 'Influence');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'pressure success claims the location and resolves' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $action->handleEvent($this->pressureResult($world, $action, $host, true));

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'location');
                Assert::same($host->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY (journal 2026-05-19 / 2026-07-20): claim is a success payoff — pressure still
            // resolves when Leshiye/etc blocks claim (availability is not claim-gated).
            'success at an unclaimable location resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $action->handleEvent($this->pressureResult($world, $action, $host, true));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure failure resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $action->handleEvent($this->pressureResult($world, $action, $host, false));

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure result for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $result = $this->pressureResult($world, $action, $host, true);
                $result->abilityId = 'other';
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
