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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd03;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd05;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd03;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03cd03_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03cd03';
    }

    /**
     * @return array{0:_03cd03,1:Action_03cd03}
     */
    private function placed(TestWorld $world): array
    {
        $card = $world->placeCard(new _03cd03(), Game::LOCATION_CITY_DOCKS, 0);
        /** @var Action_03cd03 $action */
        $action = $card->getActions()[0];
        $world->game->globals->set(Game::CURRENT_PLAYER, 1);
        return [$card, $action];
    }

    public function tests(): array
    {
        return [
            // WHY: The event itself is usually uncontrolled. Availability is "you have
            // a character here", including an engaged one. Home does not count.
            'available to a player with a character at the event, engaged or not' => function () {
                $world = new TestWorld();
                [, $action] = $this->placed($world);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody here');

                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'present');
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent absent');

                $mine->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged still counts');

                $mine->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'trigger opens the player-target state' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->placed($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'one');
                Assert::same('03cd03', $transition[0]->transition, 'state');
                Assert::same($card->Id, $transition[0]->sourceId, 'event card');
                Assert::same($action->Id, $transition[0]->internalId, 'action');
            },

            'target args are every seated player' => function () {
                $world = new TestWorld();
                [, $action] = $this->placed($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03, '');
                Assert::same(
                    [
                        ['id' => 1, 'name' => 'Player One'],
                        ['id' => 2, 'name' => 'Player Two'],
                    ],
                    $args['players'],
                    'both players'
                );
            },

            'an unknown player is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->placed($world);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03, '', 9);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            // WHY: Strictly fewer characters in play (city or Home). Turn order is the
            // player-table order from getCollectionFromDB. Equal counts do not qualify.
            // Nobody eligible discards immediately. Discard is attributed to CURRENT_PLAYER.
            'targeting the leader with no one behind discards immediately' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->placed($world);
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_PLAYER_HOME, 2);
                $world->game->globals->set(Game::CURRENT_PLAYER, 2);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03, '', 1);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no muster window');
                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'discarded');
                Assert::same($card->Id, $discard[0]->cardId, 'this card');
                Assert::same(2, $discard[0]->playerId, 'current player');
                Assert::true($discard[0]->asEffect, 'effect');
                Assert::same(Game::LOCATION_CITY_DOCKS, $discard[0]->fromLocation, 'from docks');
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::same(2, $resolved[0]->playerId, 'resolved for current player');
                Assert::same(['playerChosen'], $world->game->gamestate->transitions, 'transition');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'target cleared');
                Assert::same(null, $world->game->globals->get(Action_03cd03::REMAINING), 'queue cleared');
            },

            'eligible players are queued in turn order, skipping the target' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->placed($world);
                $world->game->playerNames[3] = 'Player Three';
                $world->placeCharacter(new GenericCharacter('P2a'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('P2b'), Game::LOCATION_PLAYER_HOME, 2);
                $world->placeCharacter(new GenericCharacter('P1'), Game::LOCATION_CITY_FORUM, 1);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03, '', 2);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(2, $transitions, 'player 1 then player 3');
                Assert::same(1, $transitions[0]->playerId, 'first');
                Assert::same(3, $transitions[1]->playerId, 'second');
                Assert::same('03cd03_2', $transitions[0]->transition, 'muster state');
                Assert::same($card->Id, $transitions[0]->sourceId, 'source');
                Assert::same([1, 3], json_decode($world->game->globals->get(Action_03cd03::REMAINING), true), 'remaining');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'still in play');
            },

            // WHY: Approach characters only. Decline (id 0) spends the seat without mustering.
            // The card discards when the last seat resolves, not when the first does.
            'each seat may decline or muster; the card discards after the last seat' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->placed($world);
                $world->game->playerNames[3] = 'Player Three';
                $world->placeCharacter(new GenericCharacter('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('LeaderHome'), Game::LOCATION_PLAYER_HOME, 2);
                $muster = $world->placeCharacter(new GenericCharacter('Recruit'), Game::LOCATION_APPROACH, 1);
                $notChar = $world->placeCard(new _03cd05(), Game::LOCATION_APPROACH, 1);
                $world->game->activePlayerId = 1;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03, '', 2);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03_2, '');
                Assert::same([$muster->Id], $args['musterIds'], 'approach characters only');
                Assert::same(2, $args['targetId'], 'target');
                Assert::same($card->Id, $args['cardId'], 'card');
                Assert::same(Game::LOCATION_CITY_DOCKS, $args['location'], 'location');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03_2, '', $notChar->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'attachment is not a muster');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03_2, '', $muster->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'player 3 still waiting');
                Assert::same(['playerChosen', 'musterResolved'], $world->game->gamestate->transitions, 'target, then this seat');

                $world->game->activePlayerId = 3;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03CD03_2, '', 0);

                $mustered = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::count(1, $mustered, 'mustered');
                Assert::same($muster->Id, $mustered[0]->characterId, 'recruit');
                Assert::same(1, $mustered[0]->playerId, 'owner');
                Assert::same(Game::LOCATION_CITY_DOCKS, $mustered[0]->location, 'at the event');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'now discarded');
                Assert::same(null, $world->game->globals->get(Action_03cd03::REMAINING), 'cleared');
            },
        ];
    }
}
