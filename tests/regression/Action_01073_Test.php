<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01073_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01073';
    }

    /** @return array{0:_01073,1:GenericCharacter,2:GenericCharacter} hat, equipped Duelist, foe */
    private function armed(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        /** @var GenericCharacter $duelist */
        $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), $location, 1);
        $hat = $world->placeCard(new _01073(), $location, 1);
        $hat->AttachedToId = $duelist->Id;
        $duelist->Attachments[] = $hat->Id;
        /** @var GenericCharacter $foe */
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), $location, 2);
        return [$hat, $duelist, $foe];
    }

    public function tests(): array
    {
        return [
            'available when equipped Duelist is in the city, ready, with an opposing character present' => function () {
                $world = new TestWorld();
                [$hat] = $this->armed($world);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when no opposing character is at the location' => function () {
                $world = new TestWorld();
                [$hat, , $foe] = $this->armed($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no foe here');
            },

            'unavailable when only allies are at the location' => function () {
                $world = new TestWorld();
                [$hat, , $foe] = $this->armed($world);
                $foe->ControllerId = 1;
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ally only');
            },

            'unavailable when equipped Duelist is engaged' => function () {
                $world = new TestWorld();
                [$hat, $duelist] = $this->armed($world);
                $duelist->Engaged = true;
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when equipped Duelist is Home (not in the city)' => function () {
                $world = new TestWorld();
                [$hat, $duelist] = $this->armed($world);
                $duelist->Location = Game::LOCATION_PLAYER_HOME;
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when hat is not attached to anyone' => function () {
                $world = new TestWorld();
                $hat = $world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'performer is the equipped character' => function () {
                $world = new TestWorld();
                [$hat, $duelist] = $this->armed($world);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one performer');
                Assert::same($duelist->Id, $performers[0]->Id, 'equipped Duelist');
            },

            'isValidTargetForAbility rejects ally and off-location foe, accepts local foe' => function () {
                $world = new TestWorld();
                [$hat, $duelist, $foe] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];

                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
            },

            // WHY: Finesse (not Combat) challenge, tagged CAVALIER_HAT so duel/resolve code can special-case it.
            'trigger sets Finesse challenge of type CAVALIER_HAT, performer, and transition 01073' => function () {
                $world = new TestWorld();
                [$hat, $duelist] = $this->armed($world);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'Finesse');
                Assert::same(Game::CAVALIER_HAT_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'challenge type');
                Assert::same($duelist->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01073', $transitions[0]->transition, 'name');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [$hat] = $this->armed($world);
                /** @var Action_01073 $action */
                $action = $hat->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'no challenge type set');
            },
        ];
    }
}
