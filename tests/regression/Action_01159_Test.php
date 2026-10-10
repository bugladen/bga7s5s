<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class Action_01159_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01159';
    }

    /**
     * Risk in hand; engaged performer at a location the player controls.
     *
     * @return array{0:_01159,1:Action_01159,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01159(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Orator'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Engaged = true;
        $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->Controller = 1;
        /** @var Action_01159 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function placeLeader(TestWorld $world, array $extraTraits = []): GenericLeader
    {
        $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
        // WHY: hasTrait reads ModifiedTraits (snapshotted at resetCard) — append both.
        foreach ($extraTraits as $trait) {
            $leader->Traits[] = $trait;
            $leader->ModifiedTraits[] = $trait;
        }
        $world->theah->leadersByPlayerId[1] = $leader;
        return $leader;
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that requires a performer' => function () {
                $action = new Action_01159();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer required');
            },

            'available with an engaged character at a controlled city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the performer is en garde' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'en garde');
            },

            'unavailable when the location is not controlled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->Controller = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are only engaged characters at controlled locations' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $ready = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_FORUM, 1);
                $ready->Engaged = true;
                $world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->Controller = 2;
                $enGarde = $world->placeCharacter(new GenericCharacter('EnGarde'), Game::LOCATION_CITY_DOCKS, 1);
                $enGarde->Engaged = false;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], array_values($ids), 'only docks engaged');
            },

            // WHY: Pattern E hand discount — Leader Hero/Diplomat grants -1; gated on this Action id.
            'Hero Leader discounts this Action by 1' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->placeLeader($world, ['Hero']);

                $explanations = [];
                $discount = $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations);
                Assert::same(1, $discount, 'Hero');
                Assert::count(1, $explanations, 'explained');
            },

            'Diplomat Leader discounts this Action by 1' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->placeLeader($world, ['Diplomat']);

                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'Diplomat');
            },

            'non-Hero non-Diplomat Leader grants no discount' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->placeLeader($world);

                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'no trait');
            },

            'discount does not apply to a different Action id' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->placeLeader($world, ['Hero']);
                $other = new Action_01159();

                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $performer, $other, $explanations), 'other id');
            },

            'null performer short-circuits the Leader discount' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->placeLeader($world, ['Hero']);

                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'null performer');
            },

            // WHY: "En garde" uses EventCardEngarded (ready), not EventCardEngaged.
            'trigger engardes the chosen performer and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'engarde');
                Assert::same($performer->Id, $engardes[0]->cardId, 'performer');
                Assert::same($risk->Id, $engardes[0]->sourceId, 'source');
                Assert::same($action->Id, $engardes[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
