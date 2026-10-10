<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01136;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;

class Action_01136_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01136';
    }

    /**
     * Alone performer at Docks; Risk in hand.
     *
     * @return array{0:_01136,1:Action_01136,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01136(), Game::LOCATION_HAND, 1);
        $alone = $world->placeCharacter(new GenericCharacter('Alone'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01136 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $alone];
    }

    private function trigger(TestWorld $world, Action_01136 $action): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction' => function () {
                Assert::instanceOf(RiskCityAction::class, new Action_01136(), 'RiskCityAction');
            },

            // WHY (prod comment): alone-at-location is the If gate; wounds are the effect, not a cost.
            'available when a controlled character is alone at a city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'unavailable when a second controlled character shares the location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not alone');
            },

            'unavailable when the only controlled character is at Home' => function () {
                $world = new TestWorld();
                [, $action, $alone] = $this->scene($world);
                $alone->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home excluded by RiskCityAction');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'an enemy at the same location does not block alone' => function () {
                $world = new TestWorld();
                [, $action, $alone] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$alone->Id], $ids, 'foe ignored');
            },

            'unwounded alone performers are still listed' => function () {
                $world = new TestWorld();
                [, $action, $alone] = $this->scene($world);
                Assert::same(0, $alone->Wounds, 'healthy');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$alone->Id], $ids, 'listed');
            },

            'trigger heals when wounded and always queues ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $alone] = $this->scene($world);
                $alone->Wounds = 2;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $alone->Id);

                $this->trigger($world, $action);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($alone->Id, $heals[0]->characterId, 'target');
                Assert::same(1, $heals[0]->wounds, 'one');
                Assert::same($risk->Id, $heals[0]->sourceId, 'source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: heal is the effect — already-healthy alone play still resolves (no-op heal).
            'trigger on an unwounded performer skips heal but still resolves' => function () {
                $world = new TestWorld();
                [, $action, $alone] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $alone->Id);

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
