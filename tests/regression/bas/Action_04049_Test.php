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

                $low->Engaged = true;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049, '', $low->Id);
                Assert::same('04049_3', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'already engaged must move');

                $low->Engaged = false;
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04049, '', $low->Id);
                Assert::same('04049_2', $world->theah->queuedOfType(EventTransition::class)[0]->playerId === 2 ? $world->theah->queuedOfType(EventTransition::class)[0]->transition : '', 'opponent chooses');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'target controller');

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
        ];
    }
}
