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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_04042_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04042';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde is a precondition. A real Artifact you control must be here,
            // equipped or loose. Home is not a city site. The target click does not move.
            // The move is engage false and cannot be a location you control.
            'moves an opposing engaged character to a city location you do not control' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _04042(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;
                $idle = $world->placeCharacter(new GenericCharacter('Idle'), Game::LOCATION_CITY_DOCKS, 2);
                $artifact = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $artifact->addTrait($world->game, 'Artifact');
                $fake = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $fake->FakeAttachment = true;
                $fake->addTrait($world->game, 'Artifact');
                /** @var Action_04042 $action */
                $action = $kaj->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'loose artifact');
                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04042, '')['ids'];
                Assert::same([$foe->Id], $ids, 'engaged only');
                Assert::false(in_array($idle->Id, $ids, true), 'en garde hidden');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04042', $world->theah->queuedOfType(\Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition::class)[0]->transition, 'opens');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not yet');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04042, '', $foe->Id);
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'target click waits');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'stored');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04042_2, '')['locationIds'];
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $destinations, true), 'you control the forum');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $destinations, true), 'already here');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $destinations, true), 'uncontrolled bazaar');

                $blocked = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04042_2, '', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'your location');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04042_2, '', [Game::LOCATION_CITY_BAZAAR]);
                // Targeting fires at the destination confirm; the move waits for it to survive.
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'hook target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'hook ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');

                // WHY: canceled hook (Cloud Bottle / UL) must not move or resolve.
                $canceled = clone $targeted[0];
                $canceled->canceled = true;
                $canceled->theah = $world->theah;
                $world->theah->takeQueuedEvents();
                $action->handleEvent($canceled);
                Assert::count(0, $world->theah->queuedEvents, 'canceled queues nothing');

                $foreign = clone $targeted[0];
                $foreign->abilityId = 'someone-else';
                $foreign->theah = $world->theah;
                $action->handleEvent($foreign);
                Assert::count(0, $world->theah->queuedEvents, 'foreign ability ignored');

                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($hook->batchId, $move->batchId, 'move shares batch');
                Assert::false($move->engage, 'en garde was only a precondition');
                Assert::same(1, $move->initiatingPlayerId, 'kaj controller');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $move->toLocation, 'bazaar');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $artifact->Location = Game::LOCATION_CITY_FORUM;
                $fake->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'fake artifact does not count');

                $kaj->Location = Game::LOCATION_PLAYER_HOME;
                $artifact->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },
        ];
    }
}
