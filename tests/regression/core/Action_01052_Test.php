<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;

class Action_01052_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01052';
    }

    private function equip(TestWorld $world, Character $host): void
    {
        $weapon = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, $host->ControllerId);
        $weapon->AttachedToId = $host->Id;
        $host->Attachments[] = $weapon->Id;
    }

    private function trigger(TestWorld $world, Action_01052 $action): void
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
            'available with wounded equipped character you control' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Wounds = 1;
                $this->equip($world, $host);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$host->Id], $ids, 'performer');
            },

            'unavailable when wounded character is not equipped' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Wounds = 1;

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not equipped');
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'no performers');
            },

            'unavailable when equipped character is not wounded' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $host);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'healthy');
            },

            'unavailable when the wounded equipped character belongs to the opponent' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Wounds = 1;
                $this->equip($world, $foe);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'opponent character');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_PLAYER_HOME, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Wounds = 1;
                $this->equip($world, $host);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger heals 1 wound and queues ActionResolved' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Wounds = 2;
                $this->equip($world, $host);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                $this->trigger($world, $action);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($host->Id, $heals[0]->characterId, 'target');
                Assert::same(1, $heals[0]->wounds, 'one wound');
                Assert::same($risk->Id, $heals[0]->sourceId, 'source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger ignores a different action id' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'trigger throws when chosen performer is unwounded' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $host);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                $threw = false;
                try {
                    $this->trigger($world, $action);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
            },

            'isValidTargetForAbility rejects opposing, unequipped and unwounded characters' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01052(), Game::LOCATION_HAND, 1);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $mine->Wounds = 1;
                $this->equip($world, $mine);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Wounds = 1;
                $this->equip($world, $foe);
                $bare = $world->placeCharacter(new GenericCharacter('Bare'), Game::LOCATION_CITY_DOCKS, 1);
                $bare->Wounds = 1;
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy'), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $healthy);

                /** @var Action_01052 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $mine)[0], 'mine');
                Assert::false($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
                Assert::false($action->isValidTargetForAbility($world->game, $bare)[0], 'unequipped');
                Assert::false($action->isValidTargetForAbility($world->game, $healthy)[0], 'unwounded');
            },
        ];
    }
}