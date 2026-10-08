<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;

class Action_01061_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01061';
    }

    private function equip(TestWorld $world, Character $host): void
    {
        $weapon = $world->placeCard(new _01048(), $host->Location, $host->ControllerId);
        $weapon->AttachedToId = $host->Id;
        $host->Attachments[] = $weapon->Id;
    }

    private function trigger(TestWorld $world, Action_01061 $action): void
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
            'available with engaged performer that has an attachment' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = true;
                $this->equip($world, $host);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one performer');
                Assert::same($host->Id, $performers[0]->Id, 'host');
            },

            'unavailable when equipped performer is not engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = false;
                $this->equip($world, $host);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'en garde already');
            },

            'unavailable when engaged performer has no attachments' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = true;

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unequipped');
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'no performers');
            },

            'unavailable when card is not in hand' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_CITY_DISCARD, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = true;
                $this->equip($world, $host);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable when only an opposing character is equipped and engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $this->equip($world, $foe);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not own performer');
            },

            'trigger en gardes the performer and resolves the action' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = true;
                $this->equip($world, $host);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                $this->trigger($world, $action);

                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engarde, 'engarde');
                Assert::same($host->Id, $engarde[0]->cardId, 'performer');
                Assert::same($risk->Id, $engarde[0]->sourceId, 'source');
                Assert::same($action->Id, $engarde[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger throws if performer is no longer engaged' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = false;
                $this->equip($world, $host);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                $threw = false;
                try {
                    $this->trigger($world, $action);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not engaged');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'trigger throws if performer has no attachment' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                /** @var Action_01061 $action */
                $action = $risk->getActions()[0];
                $threw = false;
                try {
                    $this->trigger($world, $action);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'no attachment');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
