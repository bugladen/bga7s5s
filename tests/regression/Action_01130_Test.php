<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01130;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01130;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Action_01130_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01130';
    }

    /**
     * Indomitable Will in hand; sole performer at uncontrolled Docks.
     *
     * @return array{0:_01130,1:Action_01130,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01130(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01130 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function trigger(TestWorld $world, Action_01130 $action, GenericCharacter $performer): void
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
            'is a RiskCityAction that requires a performer' => function () {
                $action = new Action_01130();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::true($action->RequiresPerformerSelected, 'needs performer');
            },

            'available when the performer is alone at an uncontrolled claimable city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when another of your characters shares the location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not alone');
            },

            'unavailable when the location is already controlled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled');
            },

            // WHY: Leshiye / IW set CanBeClaimed false — central canLocationBeClaimedBy gate.
            'unavailable when the location cannot be claimed' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'claim locked');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'getPerformersForAction lists only viable performers' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $crowded = $world->placeCharacter(new GenericCharacter('Crowded'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'alone at docks');
                Assert::false(in_array($crowded->Id, $ids, true), 'crowded excluded');
            },

            'trigger claims, stamps Indomitable Will, locks claim/uncontrol, and resolves' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                $this->trigger($world, $action, $performer);

                Assert::true($action->IsActive, 'active');
                Assert::same($performer->Id, $action->ControllingCharacterId, 'character');
                Assert::same(Game::LOCATION_CITY_DOCKS, $action->ControlledLocation, 'location');
                Assert::true($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'condition');

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'docks');
                Assert::same($performer->Id, $claims[0]->performerId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $loc = $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS);
                Assert::false($loc->CanBeClaimed, 'claim locked after');
                Assert::false($loc->CanBecomeUncontrolled, 'uncontrol locked');
            },

            'trigger when claim is blocked resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);

                $this->trigger($world, $action, $performer);

                Assert::false($action->IsActive, 'not armed');
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::false($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'no condition');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'leaving the controlled location clears the effect and queues uncontrol' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->trigger($world, $action, $performer);
                $world->theah->takeQueuedEvents();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $moved = new EventCardMoved();
                $moved->cardId = $performer->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_CITY_FORUM;
                $moved->theah = $world->theah;
                $action->handleEvent($moved);

                Assert::false($action->IsActive, 'cleared');
                Assert::false($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'condition gone');
                Assert::true(
                    $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed,
                    'claim restored'
                );
                Assert::count(1, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'uncontrol');
            },

            'discard from play clears the effect' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->trigger($world, $action, $performer);
                $world->theah->takeQueuedEvents();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $discard = new EventCardDiscardedFromPlay();
                $discard->cardId = $performer->Id;
                $discard->theah = $world->theah;
                $action->handleEvent($discard);

                Assert::false($action->IsActive, 'cleared');
                Assert::false($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'condition gone');
            },

            'character destroyed clears the effect' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->trigger($world, $action, $performer);
                $world->theah->takeQueuedEvents();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $performer->Id;
                $destroyed->theah = $world->theah;
                $action->handleEvent($destroyed);

                Assert::false($action->IsActive, 'cleared');
                Assert::false($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'condition gone');
            },

            'DuskEndOfDay clears the effect' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $this->trigger($world, $action, $performer);
                $world->theah->takeQueuedEvents();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $action->handleEvent($dusk);

                Assert::false($action->IsActive, 'cleared');
                Assert::false($performer->hasCondition(Game::INDOMITABLE_WILL_CONDITION), 'condition gone');
            },

            // WHY: endEffect is idempotent — Character + discarded Action copies may all see leave/destroy.
            'endEffect is idempotent when already cleared' => function () {
                $world = new TestWorld();
                [, , $performer] = $this->scene($world);

                Action_01130::endEffect($world->game, $performer, Game::LOCATION_CITY_DOCKS);
                Assert::count(0, $world->theah->queuedEvents, 'noop without condition');
            },
        ];
    }
}
