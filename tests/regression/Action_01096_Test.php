<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01096_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01096';
    }

    /**
     * Raton at Docks (adjacent: Forum only in a 2-player map), enemy at Forum.
     *
     * @return array{0:_01096,1:GenericCharacter,2:Action_01096}
     */
    private function scene(TestWorld $world, bool $equipEnemy = true): array
    {
        $raton = $world->placeCharacter(new _01096(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        if ($equipEnemy) {
            $this->equip($world, $foe);
        }
        /** @var Action_01096 $action */
        $action = $raton->getActions()[0];
        return [$raton, $foe, $action];
    }

    private function equip(TestWorld $world, GenericCharacter $host): _01049
    {
        $flint = $world->placeCard(new _01049(), $host->Location, $host->ControllerId);
        $flint->AttachedToId = $host->Id;
        $host->Attachments[] = $flint->Id;
        return $flint;
    }

    private function act(TestWorld $world, Action_01096 $action, int $id): void
    {
        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01096, 'highDramaPlayerTurn_01096', $id);
    }

    private function actThrows(TestWorld $world, Action_01096 $action, int $id): bool
    {
        try {
            $this->act($world, $action, $id);
        } catch (\BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'available when an adjacent enemy has an attachment' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent equipped enemy');
            },

            'unavailable when the adjacent enemy has no attachment' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to target');
            },

            'unavailable when the equipped enemy is not adjacent' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                // Bazaar is not adjacent to Docks.
                $foe->Location = Game::LOCATION_CITY_BAZAAR;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not adjacent');
            },

            'unavailable when the equipped enemy is at the same location' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location is not adjacent');
            },

            'a friendly equipped character does not enable the action' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world, false);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $this->equip($world, $ally);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ally ignored');
            },

            'an uncontrolled equipped character does not enable the action' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'unavailable to the opposing player' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not the controller');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'unavailable when Fate\'s Silence blanks Raton' => function () {
                $world = new TestWorld();
                [$raton, , $action] = $this->scene($world);
                $raton->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues the 01096 target-selection transition' => function () {
                $world = new TestWorld();
                [$raton, , $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01096', $transitions[0]->transition, 'transition name');
                Assert::same($raton->Id, $transitions[0]->sourceId, 'source Raton');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
            },

            'trigger for another action is ignored' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'args list only adjacent equipped enemies and the performer' => function () {
                $world = new TestWorld();
                [$raton, $foe, $action] = $this->scene($world);
                $bare = $world->placeCharacter(new GenericCharacter('Bare Foe'), Game::LOCATION_CITY_FORUM, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_BAZAAR, 2);
                $this->equip($world, $far);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $raton->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01096, 'highDramaPlayerTurn_01096');

                Assert::same($raton->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'only the adjacent equipped enemy');
            },

            'act moves Raton to the target location without engaging, then resolves the action' => function () {
                $world = new TestWorld();
                [$raton, $foe, $action] = $this->scene($world);

                $this->act($world, $action, $foe->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($raton->Id, $moves[0]->cardId, 'Raton moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from Docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to the target location');
                Assert::false($moves[0]->engage, 'ability move does not engage');
                Assert::same($raton->Id, $moves[0]->sourceId, 'source Raton');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability id');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'action resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            'act refuses a character that does not exist' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($this->actThrows($world, $action, 999999), 'unknown id');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'act refuses the controller\'s own character' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                Assert::true($this->actThrows($world, $action, $ally->Id), 'own character');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'act refuses a non-adjacent target' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_BAZAAR, 2);
                Assert::true($this->actThrows($world, $action, $far->Id), 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
