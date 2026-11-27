<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03020_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03020';
    }

    /**
     * @return array{0:_03020,1:Action_03020,2:GenericLeader,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03020(), Game::LOCATION_HAND, 1);
        $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->leadersByPlayerId[1] = $leader;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_03020 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $leader, $foe];
    }

    public function tests(): array
    {
        return [
            'is a risk action, not a city-only action' => function () {
                $action = new Action_03020();
                Assert::instanceOf(RiskAction::class, $action, 'risk action');
                Assert::false($action->RequiresPerformerSelected, 'the leader is implicit');
                Assert::same(403020, States::HIGH_DRAMA_PLAYER_TURN_03020, 'state');
            },

            // WHY: The leader must be in the city. getLeaderByPlayerId is the leader
            // table, not "any character at the location". Uncontrolled characters are
            // not opposing. The move sends them Home and does not engage them.
            'sends an opponent at your city Leader home' => function () {
                $world = new TestWorld();
                [$risk, $action, $leader, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03020, 'highDramaPhase03020');
                Assert::same([$foe->Id], $args['ids'], 'foe');
                Assert::same($leader->Id, $args['performerId'], 'leader');

                $loose = $world->placeCharacter(new GenericCharacter('Loose'), Game::LOCATION_CITY_DOCKS, 0);
                [$ok] = $action->isValidTargetForAbility($world->game, $loose);
                Assert::false($ok, 'uncontrolled');

                $leader->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader at home');
                $leader->Location = Game::LOCATION_CITY_DOCKS;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $action->handleEvent($triggered);
                Assert::same('03020', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $world->theah->takeQueuedEvents(); // drop the picker transition from the trigger above
                $world->game->gamestate->transitions = [];
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03020, 'x', $foe->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($foe->Id, $move->cardId, 'foe');
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'does not engage');
                Assert::same($risk->Id, $move->sourceId, 'risk');
                Assert::same($hook->batchId, $move->batchId, 'move shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: Cloud Bottle / UL cancel the hook — nothing may continue.
            'canceled CharacterTargeted does not move or resolve' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'a missing target throws before the move' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03020, 'x', 999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
