<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class Action_01103b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01103b';
    }

    /**
     * Not first player, engaged performer in city, Adaptable in hand.
     *
     * @return array{0:_01103,1:Action_01103b,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01103(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $mine->Engaged = true;
        $world->game->globals->set(Game::FIRST_PLAYER, 2);
        /** @var Action_01103b $action */
        $action = $risk->getActions()[1];
        return [$risk, $action, $mine];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that depends on not being first player' => function () {
                $action = new Action_01103b();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatDependsOnNotBeingFirstPlayer::class, $action, 'not first');
            },

            'available when not first player with an engaged city character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when first player (without override)' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::FIRST_PLAYER, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'first player');
            },

            // WHY: OVERRIDE_AS_NOT_FIRST_PLAYER lets first player use not-first abilities (Action_01090 family).
            'available to first player when OVERRIDE_AS_NOT_FIRST_PLAYER is set' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::FIRST_PLAYER, 1);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'override');
            },

            'unavailable when no engaged characters in the city' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $mine->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'en garde only');
            },

            'performers are only engaged city characters' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $ready = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_FORUM, 1);
                $ready->Engaged = false;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$mine->Id], $ids, 'engaged only');
            },

            'trigger engardes the chosen performer and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $mine] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                // WHY: "En garde" uses EventCardEngarded (ready), not EventCardEngaged.
                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'engarde');
                Assert::same($mine->Id, $engardes[0]->cardId, 'performer');
                Assert::same($risk->Id, $engardes[0]->sourceId, 'source');
                Assert::same($action->Id, $engardes[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
