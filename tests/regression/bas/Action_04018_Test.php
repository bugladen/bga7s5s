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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04018;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04018_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04018';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde. The target is a controlled enemy at an adjacent city location.
            // At two players the Docks are adjacent to the Forum, not the Bazaar. A Sorcerer
            // or Monster controller with a hand must discard. The move does not engage.
            'moves to the adjacent enemy, then that sorcerer discards' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $risk = $world->placeCard(new _04018(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_FORUM, 2);
                $bazaar = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $hand = $world->placeCard(new _04006(), Game::LOCATION_HAND, 2);
                /** @var Action_04018 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'forum enemy');
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $performer->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04018, '');
                Assert::true(in_array($sorcerer->Id, $args['ids'], true), 'adjacent');
                Assert::false(in_array($bazaar->Id, $args['ids'], true), 'bazaar is not adjacent at two players');

                // WHY: pick queues ONLY CharacterTargeted (Cloud Bottle / UL / Maryam can cancel);
                // move + trailing discard come from the surviving-targeting handler.
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04018, '', $sorcerer->Id);
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($sorcerer->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no discard prompt yet');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'to the hub');

                $canceled = clone $targeted[0];
                $canceled->canceled = true;
                $canceled->theah = $world->theah;
                $action->handleEvent($canceled);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'cancel: no move');
                Assert::same([], $action->PlayersToDiscard, 'cancel: no discard list');

                $survived = clone $targeted[0];
                $survived->canceled = false;
                $survived->theah = $world->theah;
                $action->handleEvent($survived);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($targeted[0]->batchId, $move->batchId, 'shared batch');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::same([2], $action->PlayersToDiscard, 'sorcerer controller');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved first');
                Assert::same('04018_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'discard');

                $world->game->currentPlayerId = 2;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04018_2, '', $hand->Id);
                Assert::true($world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->asEffect, 'effect');

                $threw = false;
                try {
                    $world->game->currentPlayerId = 1;
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04018_2, '', $hand->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'the actor does not discard');
            },
        ];
    }
}
