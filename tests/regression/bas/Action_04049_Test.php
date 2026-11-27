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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04049_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04049';
    }

    public function tests(): array
    {
        return [
            // WHY: Target must have strictly lower Influence. An already engaged target
            // skips the engage choice and must move. Decline moves with engage false,
            // and the initiating player is the ability owner. At two players the Bazaar
            // is not adjacent to the Docks.
            'offers engage or an adjacent move' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $risk = $world->placeCard(new _04049(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new _04042(), Game::LOCATION_CITY_DOCKS, 1);
                $low = $world->placeCharacter(new GenericCharacter('Low'), Game::LOCATION_CITY_DOCKS, 2);
                $peer = $world->placeCharacter(new _04042(), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04049 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'lower influence');
                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049, '')['ids'];
                Assert::same([$low->Id], $ids, 'equal influence hidden');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $world->fireOn($risk, $triggered);
                Assert::same('04049', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'opens');

                // WHY: pick queues ONLY CharacterTargeted (Cloud Bottle / UL cancel it); the prompt
                // opens from the survive hook. Already Engaged → auto-move stage.
                $world->theah->takeQueuedEvents();
                $low->Engaged = true;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049, '', $low->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($low->Id, $targeted[0]->targetId, 'target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no prompt beside pick');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'target not stored before survive');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('04049_3', $transitions[0]->transition, 'already engaged must move');
                Assert::same($hook->batchId, $transitions[0]->batchId, 'shared batch');
                Assert::same($low->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target stored on survive');

                $low->Engaged = false;
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049, '', $low->Id);
                $hook = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $world->theah->takeQueuedEvents();
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('04049_2', $transitions[0]->transition, 'opponent chooses');
                Assert::same(2, $transitions[0]->playerId, 'target controller');
                Assert::same($hook->batchId, $transitions[0]->batchId, 'shared batch');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049_2, '', 1);
                $engage = $world->theah->queuedOfType(EventCardEngaged::class)[0];
                Assert::same($low->Id, $engage->cardId, 'they engage');
                Assert::same($risk->Id, $engage->sourceId, 'ability owner');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'engage resolves');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049_2, '', 2);
                Assert::same('declined', end($world->game->gamestate->transitions), 'must move');

                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049_3, '')['locationIds'];
                Assert::same([Game::LOCATION_CITY_FORUM], $destinations, 'forum only');

                $blocked = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049_3, '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'bazaar is not adjacent');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049_3, '', [Game::LOCATION_CITY_FORUM]);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::false($move->engage, 'move does not engage');
                Assert::same(1, $move->initiatingPlayerId, 'ability owner initiates');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');

                $peer->ModifiedInfluence = 1;
                [$ok] = $action->isValidTargetForAbility($world->game, $peer);
                Assert::true($ok, 'now lower');
                $peer->ModifiedInfluence = $performer->ModifiedInfluence;
                [$equal] = $action->isValidTargetForAbility($world->game, $peer);
                Assert::false($equal, 'not strictly lower');
            },

            // WHY: Cloud Bottle / UL cancel the hook — no prompt, no move, no stale CHOSEN_TARGET.
            'canceled CharacterTargeted opens nothing' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04049(), Game::LOCATION_HAND, 1);
                $low = $world->placeCharacter(new GenericCharacter('Low'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04049 $action */
                $action = $risk->getActions()[0];

                $hook = new EventCharacterTargeted();
                $hook->playerId = 1;
                $hook->targetId = $low->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'no target stored');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04049(), Game::LOCATION_HAND, 1);
                $low = $world->placeCharacter(new GenericCharacter('Low'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04049 $action */
                $action = $risk->getActions()[0];

                $hook = new EventCharacterTargeted();
                $hook->targetId = $low->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
