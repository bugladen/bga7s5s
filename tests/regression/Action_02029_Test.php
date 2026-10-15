<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Action_02029_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02029';
    }

    /**
     * Diplomat at claimable Docks with Renown 0 (1 Diplomat > 0 Renown).
     *
     * @return array{0:_02029,1:Action_02029,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $renown = 0): array
    {
        $risk = $world->placeCard(new _02029(), Game::LOCATION_HAND, 1);
        $diplomat = $world->placeCharacter(
            new GenericCharacter('Dip', ['Diplomat']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, $renown);
        /** @var Action_02029 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $diplomat];
    }

    private function trigger(TestWorld $world, Action_02029 $action): void
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
            'is a RiskAction that requires performer' => function () {
                $action = new Action_02029();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // Diplomat Action: more Diplomats at performer location than Renown AND claimable.
            'available when Diplomats exceed Renown at a claimable location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Diplomats do not exceed Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'tie/less');
            },

            'available with two Diplomats against Renown 1' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);
                $world->placeCharacter(
                    new GenericCharacter('Dip2', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::true($action->isAvailableToPlayer(1, $world->theah), '2 > 1');
            },

            // WHY: Indomitable Will / claim gates go through canLocationBeClaimedBy.
            'unavailable when location cannot be claimed' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unclaimable');
            },

            'unavailable without Diplomat trait' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world, 0);
                $diplomat->ModifiedTraits = ['Mercenary'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Diplomat');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, 0);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are Diplomats whose location beats Renown and is claimable' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world, 0);
                $blocked = $world->placeCharacter(
                    new GenericCharacter('Blocked Dip', ['Diplomat']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
                $world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->CanBeClaimed = false;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$diplomat->Id], $ids, 'only claimable');
                Assert::false(in_array($blocked->Id, $ids, true), 'blocked out');
            },

            // Trigger: LocationClaimed + ActionResolved.
            'trigger claims the performer location and resolves' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                $this->trigger($world, $action);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'Docks');
                Assert::same(1, $claims[0]->playerId, 'controller');
                Assert::same($diplomat->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // Or notify if cannot claim.
            'trigger on unclaimable location announces, claims nothing, still resolves' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $notes = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'message');
                Assert::count(1, $notes, 'announced');
            },
        ];
    }
}
