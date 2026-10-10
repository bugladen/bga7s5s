<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01131_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01131';
    }

    /**
     * Unequipped performer at Docks with an opposing character; Risk in hand.
     *
     * @return array{0:_01131,1:Action_01131,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01131(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01131 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    private function trigger(TestWorld $world, Action_01131 $action): void
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
            // WHY (journal 2026-10-07-08): IAbilityThatTargetsCharacters is for Dabney-style
            // cancel paths; I&V never fires CharacterTargeted at announce — target is later.
            'is a RiskAction that targets characters and needs a performer' => function () {
                $action = new Action_01131();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when an unequipped challenger faces opposition in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the only performer has an attachment' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Attachments[] = 999;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'equipped');
            },

            'unavailable when no opposing character shares a city location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposition');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are unequipped city characters with opposition' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $equipped = $world->placeCharacter(new GenericCharacter('Equipped'), Game::LOCATION_CITY_DOCKS, 1);
                $equipped->Attachments[] = 42;
                $alone = $world->placeCharacter(new GenericCharacter('Alone'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'unequipped with foe');
                Assert::false(in_array($equipped->Id, $ids, true), 'equipped excluded');
                Assert::false(in_array($alone->Id, $ids, true), 'no opposition excluded');
            },

            'isValidTarget rejects own characters and different locations' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                [$okFoe] = $action->isValidTargetForAbility($world->game, $foe);
                [$okAlly, $allyMsg] = $action->isValidTargetForAbility($world->game, $ally);
                [$okFar, $farMsg] = $action->isValidTargetForAbility($world->game, $far);

                Assert::true($okFoe, 'foe ok');
                Assert::false($okAlly, 'ally rejected');
                Assert::true($allyMsg !== '', 'ally message');
                Assert::false($okFar, 'far rejected');
                Assert::true($farMsg !== '', 'far message');
            },

            // WHY: no createActionResolvedEvent — resolved at end of duel (comment in source).
            'trigger stamps Combat + Iron and Velvet challenge type and queues 01131' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $this->trigger($world, $action);

                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat');
                Assert::same(
                    Game::IRON_AND_VELVET_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'I&V type'
                );
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01131', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'trigger ignores another action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
