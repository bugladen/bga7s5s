<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01087;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class Action_01087_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01087';
    }

    /** @return array{0:_01087,1:Action_01087,2:Character} */
    private function scene(TestWorld $world, array $traits = []): array
    {
        $risk = $world->placeCard(new _01087(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Engaged', $traits), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Engaged = true;

        /** @var Action_01087 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function trigger(TestWorld $world, Action_01087 $action, Character $performer): void
    {
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that needs a selected performer' => function () {
                $action = new Action_01087();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer selected');
            },

            'available with an engaged non-Mercenary character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performers');
            },

            'available with an engaged character at Player Home' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home counts');
            },

            // WHY: En garde only does something to an engaged character — an un-engaged performer is not offered.
            'unavailable when the only non-Mercenary is not engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = false;

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not engaged');
                Assert::same([], array_values($action->getPerformersForAction(1, $world->theah)), 'no performers');
            },

            'unavailable when the only engaged character is a Mercenary' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, ['Mercenary']);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Mercenary');
                Assert::same([], array_values($action->getPerformersForAction(1, $world->theah)), 'no performers');
            },

            'performers exclude engaged Mercenaries and opponents' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $merc->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;

                $ids = array_values(array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)));
                Assert::same([$performer->Id], $ids, 'only the engaged non-Mercenary of ours');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            // WHY: inline Action — no transition state; resolves in the same handleEvent.
            'trigger en gardes the chosen performer and resolves the Action inline' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);

                $this->trigger($world, $action, $performer);

                $queued = $world->theah->queuedEvents;
                Assert::count(2, $queued, 'engarde + resolved');
                Assert::instanceOf(EventCardEngarded::class, $queued[0], 'engarde first');
                Assert::same($performer->Id, $queued[0]->cardId, 'performer');
                Assert::same(1, $queued[0]->playerId, 'player');
                Assert::same($risk->Id, $queued[0]->sourceId, 'source');
                Assert::same($action->Id, $queued[0]->abilityId, 'ability');
                Assert::instanceOf(EventActionResolved::class, $queued[1], 'resolved second');
                Assert::same(1, $queued[1]->playerId, 'resolved player');
            },

            'trigger ignores another action id' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
