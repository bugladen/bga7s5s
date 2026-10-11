<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01176;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01176;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01176_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01176';
    }

    /**
     * Risk in hand; wounded character in play (any controller — printed "Target character").
     *
     * @return array{0:_01176,1:Action_01176,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $wounds = 1, int $controllerId = 1): array
    {
        $risk = $world->placeCard(new _01176(), Game::LOCATION_HAND, 1);
        $target = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_DOCKS, $controllerId);
        $target->Wounds = $wounds;
        /** @var Action_01176 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $target];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets characters and requires a performer' => function () {
                $action = new Action_01176();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when any character in play is wounded' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'available when only an opposing character is wounded' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'oppose wounded');
            },

            'unavailable when nobody is wounded' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'healthy');
                unset($target);
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            // WHY: printed "Target character" — performers are any wounded in play, not only yours.
            'performers are all wounded characters in play' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $foe->Wounds = 2;
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy'), Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($mine->Id, $ids, true), 'mine');
                Assert::true(in_array($foe->Id, $ids, true), 'foe');
                Assert::false(in_array($healthy->Id, $ids, true), 'healthy excluded');
            },

            // WHY: -1 cost while targeting Hero or Scoundrel — gated on this Action id so sticky
            // traits cannot discount unrelated hand Actions (same pattern as Action_01171).
            'Hero or Scoundrel target discounts this Action by 1' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $hero = new GenericCharacter('Hero', ['Hero']);
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $hero, $action, $explanations), 'Hero');
                Assert::count(1, $explanations, 'explained');

                $scoundrel = new GenericCharacter('Scoundrel', ['Scoundrel']);
                $scoundrelExplanations = [];
                Assert::same(
                    1,
                    $action->getActionFromHandDiscount($world->theah, $scoundrel, $action, $scoundrelExplanations),
                    'Scoundrel'
                );

                $plain = new GenericCharacter('Plain');
                $plainExplanations = [];
                Assert::same(
                    0,
                    $action->getActionFromHandDiscount($world->theah, $plain, $action, $plainExplanations),
                    'plain'
                );
            },

            // WHY: no custom HD state — trigger heals immediately (no EventTransition).
            'trigger heals the chosen performer then ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $target] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no transition');
                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($target->Id, $heals[0]->characterId, 'target');
                Assert::same(1, $heals[0]->wounds, 'one wound');
                Assert::same($risk->Id, $heals[0]->sourceId, 'source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCharacterBeingHealed::class, $order, true)
                        < array_search(EventActionResolved::class, $order, true),
                    'heal before resolved'
                );
            },

            'isValidTargetForAbility rejects unwounded characters' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy'), Game::LOCATION_CITY_DOCKS, 1);

                [$ok,] = $action->isValidTargetForAbility($world->game, $healthy);
                Assert::false($ok, 'healthy');
            },

            'isValidTargetForAbility accepts wounded characters' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                [$ok,] = $action->isValidTargetForAbility($world->game, $target);
                Assert::true($ok, 'wounded');
            },
        ];
    }
}
