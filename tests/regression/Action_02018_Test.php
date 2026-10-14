<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;

class Action_02018_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02018';
    }

    /**
     * Divine Discipline in hand; Zealot at Docks (+ optional non-Zealot / foe).
     *
     * @return array{0:_02018,1:Action_02018,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02018(), Game::LOCATION_HAND, 1);
        $zealot = $world->placeCharacter(
            new GenericCharacter('Zealot', ['Zealot']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Action_02018 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $zealot];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction requiring performer' => function () {
                $action = new Action_02018();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with Zealot in city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without city Zealot' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02018(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_02018 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no zealot');
            },

            'performers filtered to city Zealots' => function () {
                $world = new TestWorld();
                [, $action, $zealot] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $homeZealot = $world->placeCharacter(
                    new GenericCharacter('Home Zealot', ['Zealot']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$zealot->Id], $ids, 'city zealot only');
                Assert::false(in_array($plain->Id, $ids, true), 'plain');
                Assert::false(in_array($homeZealot->Id, $ids, true), 'home');
            },

            // WHY: wound ALL characters at performer location + ActionResolved.
            'EventActionTriggered wounds all at performer location and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $zealot] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $zealot->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $woundIds = array_map(fn($w) => $w->characterId, $wounds);
                sort($woundIds);
                $expected = [$zealot->Id, $ally->Id, $foe->Id];
                sort($expected);
                Assert::same($expected, $woundIds, 'all at docks');
                Assert::false(in_array($elsewhere->Id, $woundIds, true), 'not forum');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
