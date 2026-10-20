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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd13;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd13;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03cd13_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03cd13';
    }

    /**
     * @return array{0:_03cd13,1:Action_03cd13,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $card = $world->placeCard(new _03cd13(), Game::LOCATION_CITY_DOCKS, 0);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_03cd13 $action */
        $action = $card->getActions()[0];
        $world->game->setPlayerReknown(1, 1);
        $world->game->setPlayerReknown(2, 4);
        $world->game->activePlayerId = 1;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$card, $action, $performer];
    }

    public function tests(): array
    {
        return [
            // WHY: Once per player per day, via playersUsed, not the Action Used flag.
            // Claimable location and a strictly richer opponent are both required.
            // An engaged character at the location still satisfies the parent gate.
            'available when you are behind on Renown and the location can be claimed' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->scene($world);
                Assert::true($action->RequiresPerformerSelected, 'needs a performer');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'behind');
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'player 2 is ahead and has no character');

                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged still opens the action');
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'but no performer button');
                $performer->Engaged = false;
                Assert::same([$performer], $action->getPerformersForAction(1, $world->theah), 'ready performer');

                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unclaimable');
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = true;

                $world->game->setPlayerReknown(1, 4);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'tied with the leader');
                $world->game->setPlayerReknown(1, 1);

                $performer->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not at the card');
            },

            'trigger opens the target-player state' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'one');
                Assert::same('03cd13', $transition[0]->transition, 'state');
                Assert::same($card->Id, $transition[0]->sourceId, 'card');
                Assert::same($action->Id, $transition[0]->internalId, 'action');
            },

            'args are opponents with strictly more Renown' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->scene($world);
                $world->game->playerNames[3] = 'Player Three';
                $world->game->setPlayerReknown(3, 1);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '');
                Assert::same([['id' => 2, 'name' => 'Player Two']], $args['players'], 'only the leader');
                Assert::same($card->Id, $args['cardId'], 'card');
                Assert::same($performer->Id, $args['performerId'], 'performer');
            },

            'a peer or a stranger is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                foreach ([1, 9] as $id) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', $id);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejected ' . $id);
                }
            },

            'a performer you do not control is rejected' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->ControllerId = 2;

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', 2);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY: Success clears Used (the per-player list is the real lock) and still
            // engages. If the location becomes unclaimable before the click, engage and
            // the lock still happen, and no claim is queued.
            'a legal target engages the performer and claims' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->scene($world);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', 2);

                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engage, 'engage');
                Assert::same($performer->Id, $engage[0]->cardId, 'performer');
                Assert::same($card->Id, $engage[0]->sourceId, 'source');
                Assert::same($action->Id, $engage[0]->abilityId, 'ability');

                $claim = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claim, 'claim');
                Assert::same(1, $claim[0]->playerId, 'player');
                Assert::same($performer->Id, $claim[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claim[0]->location, 'docks');

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::same(1, $resolved[0]->playerId, 'resolved');
                Assert::false($action->Used, 'Used flag cleared');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked for player 1');
                Assert::same(['playerChosen'], $world->game->gamestate->transitions, 'transition');
                Assert::same(
                    [['playerId' => 1, 'playerName' => 'Player One', 'playerColor' => '000001']],
                    $card->getCrabsInABucketUsedListData($world->game),
                    'used list'
                );
            },

            'unclaimable at resolution still engages and locks the player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', 2);

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engaged');
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked');
            },

            'using it again the same day is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', 2);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;

                $threw = false;
                try {
                    $action->eventCheck($triggered);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('already used', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'rejected');
            },

            'Dusk clears the per-player lock' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD13, '', 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'locked');

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $action->handleEvent($dusk);

                Assert::true($card->IsUpdated, 'rewritten');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available again');
                Assert::same([], $card->getCrabsInABucketUsedListData($world->game), 'list cleared');
            },
        ];
    }
}
