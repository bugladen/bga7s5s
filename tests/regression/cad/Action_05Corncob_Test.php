<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Corncob;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Corncob_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Corncob';
    }

    /**
     * @return array{world: TestWorld, corncob: _05Corncob, action: Action_05Corncob, bow: _04017, wealth: _04027}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $corncob = $world->placeCharacter(new _05Corncob(), Game::LOCATION_CITY_DOCKS, 1);
        $bow = $world->placeCard(new _04017(), $world->game->getPlayerDiscardDeckName(2), 2);
        $bow->OwnerId = 2;
        $bow->WealthCost = 1;
        // Pay card for equip cost 1.
        $wealth = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
        /** @var Action_05Corncob $action */
        $action = $corncob->getActions()[0];

        return compact('world', 'corncob', 'action', 'bow', 'wealth');
    }

    public function tests(): array
    {
        return [
            // WHY: Covert = uncontrolled City. Weapon in any discard. Affordable host at location.
            'is only offered at an uncontrolled city with an affordable Weapon in a discard pile' => function () {
                ['world' => $world, 'corncob' => $corncob, 'action' => $action, 'bow' => $bow] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $corncob->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home is not Covert');
                $corncob->Location = Game::LOCATION_CITY_DOCKS;

                $bow->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in discard');
                $bow->Location = $world->game->getPlayerDiscardDeckName(2);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
            },

            'also finds Weapons in the City Discard' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow] = $this->scene();
                $bow->Location = Game::LOCATION_CITY_DISCARD;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city discard');
            },

            'queues the Weapon chooser on ActionTriggered' => function () {
                ['world' => $world, 'corncob' => $corncob, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($corncob, $triggered);
                Assert::same('05Corncob', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },

            'Weapon pick then host pick takes control into hand and enters pay' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow, 'corncob' => $corncob] = $this->scene();

                $cards = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB, '')['cards'];
                Assert::same([$bow->Id], array_map(fn($c) => $c['id'], $cards), 'weapon listed');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB, '', $bow->Id);
                Assert::same('weaponChosen', end($world->game->gamestate->transitions), 'to host');

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2, '')['ids'];
                Assert::true(in_array($corncob->Id, $ids, true), 'Cobb is a host');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2, '', $corncob->Id);
                Assert::same(1, $bow->ControllerId, 'took control');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'leave discard');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'to hand');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay state');
                Assert::same('05Corncob_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pay step');
            },

            'pay finalizes the equip' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow, 'corncob' => $corncob, 'wealth' => $wealth] = $this->scene();

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB, '', $bow->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2, '', $corncob->Id);

                // Simulate hand arrival from the queued AddedToHand event.
                $bow->Location = Game::LOCATION_HAND;
                $bow->ControllerId = 1;
                $world->game->globals->set(Game::DISCOUNT, 0);
                $world->game->globals->set(Game::DISCOUNT_EXPLAINATIONS, '');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_3, '', [$wealth->Id]);
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'paid');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'equipped');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'rejects Covert resolve after the location becomes controlled' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow] = $this->scene();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB, '', $bow->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'Covert recheck');
            },

            'city-discard take-control uses the city remove event' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow, 'corncob' => $corncob] = $this->scene();
                $bow->Location = Game::LOCATION_CITY_DISCARD;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB, '', $bow->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05CORNCOB_2, '', $corncob->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromCityDiscardPile::class), 'city leave');
            },
        ];
    }
}
