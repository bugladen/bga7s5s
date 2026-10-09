<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01103a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Action_01103a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01103a';
    }

    /**
     * First player, Pirate performer at claimable Docks, Adaptable in hand.
     *
     * @return array{0:_01103,1:Action_01103a,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01103(), Game::LOCATION_HAND, 1);
        $pirate = $world->placeCharacter(
            new GenericCharacter('Pirate', ['Pirate']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $world->game->globals->set(Game::FIRST_PLAYER, 1);
        /** @var Action_01103a $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $pirate];
    }

    private function trigger(TestWorld $world, Action_01103a $action): void
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
                Assert::instanceOf(RiskCityAction::class, new Action_01103a(), 'RiskCityAction');
            },

            'available when first player has a Pirate at a claimable city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when not the first player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::FIRST_PLAYER, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not first');
            },

            'unavailable without a Pirate performer' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                // WHY: Empty ModifiedTraits resets Traits via hasTrait — leave a non-empty strip.
                $pirate->ModifiedTraits = ['Mercenary'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Pirate');
            },

            // WHY: Indomitable Will / claim gates go through canLocationBeClaimedBy.
            'unavailable when the Pirate\'s location cannot be claimed' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unclaimable');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are only Pirates at claimable locations' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_FORUM, 1);
                $blocked = $world->placeCharacter(
                    new GenericCharacter('Blocked Pirate', ['Pirate']),
                    Game::LOCATION_CITY_BAZAAR,
                    1
                );
                $world->theah->getCityLocation(Game::LOCATION_CITY_BAZAAR)->CanBeClaimed = false;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$pirate->Id], $ids, 'only claimable Pirate');
                Assert::false(in_array($crew->Id, $ids, true), 'no Pirate trait');
                Assert::false(in_array($blocked->Id, $ids, true), 'unclaimable');
            },

            'trigger claims the performer location and resolves' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);

                $this->trigger($world, $action);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'Docks');
                Assert::same(1, $claims[0]->playerId, 'controller');
                Assert::same($pirate->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: same race as Action_01095a — still resolve (and pay) if claimability flipped mid-play.
            'trigger on an unclaimable location announces, claims nothing, still resolves' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
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
