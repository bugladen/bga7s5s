<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01026_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01026';
    }

    /**
     * Red Hand performer + opposing foe at the Docks; Risk in hand.
     *
     * @return array{0:_01026,1:Action_01026,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Red Hand', ['Red Hand']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_01026 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    private function targeted(TestWorld $world, Action_01026 $action, _01026 $risk, GenericCharacter $target, bool $canceled): EventCharacterTargeted
    {
        $targeted = new EventCharacterTargeted();
        $targeted->abilityId = $action->Id;
        $targeted->targetId = $target->Id;
        $targeted->sourceId = $risk->Id;
        $targeted->batchId = 77;
        $targeted->canceled = $canceled;
        $targeted->theah = $world->theah;
        return $targeted;
    }

    public function tests(): array
    {
        return [
            'available with Red Hand performer facing opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Red Hand or without opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Red Hand');

                $world2 = new TestWorld();
                $risk2 = $world2->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $world2->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                /** @var Action_01026 $action2 */
                $action2 = $risk2->getActions()[0];
                Assert::false($action2->isAvailableToPlayer(1, $world2->theah), 'no foe');
            },

            'trigger queues transition 01026' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01026', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'isValidTargetForAbility requires opposing character at performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01026(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(
                    new GenericCharacter('Red Hand', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01026 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe ok');
                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally rejected');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'wrong loc');
            },

            // WHY: destroying the performer is the COST (always applies); Manipulate +
            // ActionResolved wait for EventCharacterTargeted so Cloud Bottle / UL can cancel.
            'act destroys performer (cost) and queues only CharacterTargeted for the effect' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01026,
                    'highDramaPhase01026',
                    $foe->Id
                );

                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroy');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'surviving CharacterTargeted engages en garde target and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);

                $action->handleEvent($this->targeted($world, $action, $risk, $foe, false));

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'engage');
                Assert::same(77, $engages[0]->batchId, 'shared batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'surviving CharacterTargeted sends already-engaged target Home instead of engaging' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);
                $foe->Engaged = true;

                $action->handleEvent($this->targeted($world, $action, $risk, $foe, false));

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move home');
                Assert::same($foe->Id, $moves[0]->cardId, 'target');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'home');
                Assert::same(77, $moves[0]->batchId, 'shared batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'canceled CharacterTargeted queues no effect' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);

                $action->handleEvent($this->targeted($world, $action, $risk, $foe, true));

                Assert::count(0, $world->theah->queuedEvents, 'UL / Cloud Bottle cancel');
            },
        ];
    }
}
