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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Serenissima_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Serenissima';
    }

    /**
     * @return array{world: TestWorld, patricia: _05Serenissima, action: Action_05Serenissima, discovery: _04039, engaged: GenericCharacter, ready: GenericCharacter}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
        $discovery = $world->placeCard(new _04039(), $world->game->getPlayerDiscardDeckName(1), 1);
        $discovery->OwnerId = 1;
        $engaged = $world->placeCharacter(new GenericCharacter('Engaged Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $engaged->Engaged = true;
        $ready = $world->placeCharacter(new GenericCharacter('Ready Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $ready->Engaged = false;
        /** @var Action_05Serenissima $action */
        $action = $patricia->getActions()[0];

        return compact('world', 'patricia', 'action', 'discovery', 'engaged', 'ready');
    }

    public function tests(): array
    {
        return [
            // WHY: Covert = uncontrolled City. Left of • = send Bureaucracy/Discovery from
            // own discard (availability). Right of • = En Garde effect — Engaged is a
            // valid-target filter for that effect (not a cost), and also gates offer so
            // state 2 cannot softlock with empty ids. No Engage printed on the Action.
            'is only offered at an uncontrolled city with discard card and Engaged target' => function () {
                ['world' => $world, 'patricia' => $patricia, 'action' => $action, 'discovery' => $discovery, 'engaged' => $engaged] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $patricia->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home is not Covert');
                $patricia->Location = Game::LOCATION_CITY_DOCKS;

                $discovery->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in discard');
                $discovery->Location = $world->game->getPlayerDiscardDeckName(1);

                $engaged->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Engaged target');
                $engaged->Engaged = true;

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
            },

            'queues the discard chooser on ActionTriggered' => function () {
                ['world' => $world, 'patricia' => $patricia, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($patricia, $triggered);
                Assert::same('05Serenissima', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },

            'discard pick then Engaged target sends to Locker and Engardes; ready not highlighted' => function () {
                ['world' => $world, 'action' => $action, 'discovery' => $discovery, 'engaged' => $engaged, 'ready' => $ready] = $this->scene();

                $cards = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA, '')['cards'];
                Assert::same([$discovery->Id], array_map(fn($c) => $c['id'], $cards), 'discovery listed');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA, '', $discovery->Id);
                Assert::same('cardChosen', end($world->game->gamestate->transitions), 'to target');
                Assert::same($discovery->Id, (int)$world->game->globals->get(Game::CHOSEN_CARD), 'stashed');

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2, '')['ids'];
                Assert::true(in_array($engaged->Id, $ids, true), 'engaged offered');
                Assert::false(in_array($ready->Id, $ids, true), 'ready excluded');

                [$ok] = $action->isValidTargetForAbility($world->game, $ready);
                Assert::false($ok, 'ready rejected');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2, '', $engaged->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'remove');
                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'locker');
                Assert::same($engaged->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'engarde');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'done');
            },

            'rejects Covert resolve after the location becomes controlled' => function () {
                ['world' => $world, 'action' => $action, 'discovery' => $discovery, 'engaged' => $engaged] = $this->scene();

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA, '', $discovery->Id);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SERENISSIMA_2, '', $engaged->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'Covert recheck');
            },
        ];
    }
}
