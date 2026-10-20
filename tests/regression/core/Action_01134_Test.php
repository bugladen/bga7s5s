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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01134;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01134;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01134_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01134';
    }

    /** @return array{0:_01134,1:Action_01134,2:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01134(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $performer->Influence = 2;
        $performer->ModifiedInfluence = 2;
        /** @var Action_01134 $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$risk, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction Sorcerer ability that needs a performer' => function () {
                $action = new Action_01134();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a Sorcerer in play' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Sorcerer' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = [];
                $performer->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'trigger queues transition 01134' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01134', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'state _1 args list player decks plus City Deck' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134,
                    'highDramaPlayerTurn_01134'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                $ids = array_column($args['decks'], 'id');
                Assert::true(in_array(1, $ids, true), 'player 1');
                Assert::true(in_array(2, $ids, true), 'player 2');
                Assert::true(in_array(0, $ids, true), 'city deck');
            },

            'choosing a faction deck peeks top cards, starts Sorcery, and transitions' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $a = $world->placeCard(new _01131(), 'Deck-2', 2);
                $b = $world->placeCard(new _01131(), 'Deck-2', 2);
                $world->game->topFactionCards = [['id' => $a->Id], ['id' => $b->Id]];

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134,
                    'highDramaPlayerTurn_01134',
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(2, $cards, 'peeked');
                Assert::same($a->Id, $cards[0]->id, 'first');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0]->sourceId, 'source');
                Assert::same(['opponentChosen'], $world->game->gamestate->transitions, 'next');
            },

            'choosing City Deck peeks topCityCards' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $c = $world->placeCard(new _01131(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $c->Id]];

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134,
                    'x',
                    0
                );

                Assert::same(0, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'city');
                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::same($c->Id, $cards[0]->id, 'city card');
            },

            'invalid opponent id throws' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01134,
                        'x',
                        99
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid');
            },

            'discarding peeked cards moves them to discard and keeps the rest' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $keep = $world->placeCard(new _01131(), 'Deck-2', 2);
                $dump = $world->placeCard(new _01131(), 'Deck-2', 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    (object)['id' => $keep->Id],
                    (object)['id' => $dump->Id],
                ]));

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134_2,
                    'highDramaPlayerTurn_01134_2',
                    [$dump->Id]
                );

                Assert::same('Discard-2', $dump->Location, 'discarded');
                $remaining = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(1, $remaining, 'one left');
                Assert::same($keep->Id, $remaining[0]->id, 'kept');
                Assert::same(['cardsChosen'], $world->game->gamestate->transitions, 'next');
            },

            'discarding more than performer Influence throws' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Influence = 1;
                $performer->ModifiedInfluence = 1;
                $a = $world->placeCard(new _01131(), 'Deck-2', 2);
                $b = $world->placeCard(new _01131(), 'Deck-2', 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    (object)['id' => $a->Id],
                    (object)['id' => $b->Id],
                ]));

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01134_2,
                        'highDramaPlayerTurn_01134_2',
                        [$a->Id, $b->Id]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'over Influence');
                Assert::same('Deck-2', $a->Location, 'a stays');
                Assert::same('Deck-2', $b->Location, 'b stays');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'pass on discard proceeds without moving cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01134_2);
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'reordering remaining cards inserts them on top of the deck' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $a = $world->placeCard(new _01131(), 'Deck-2', 2);
                $b = $world->placeCard(new _01131(), 'Deck-2', 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    (object)['id' => $a->Id],
                    (object)['id' => $b->Id],
                ]));

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134_3,
                    'highDramaPlayerTurn_01134_3',
                    [$b->Id, $a->Id]
                );

                Assert::same(
                    [
                        ['id' => $b->Id, 'deck' => 'Deck-2', 'onTop' => true],
                        ['id' => $a->Id, 'deck' => 'Deck-2', 'onTop' => true],
                    ],
                    $world->game->deckInserts,
                    'order'
                );
                Assert::same(['cardsSorted'], $world->game->gamestate->transitions, 'next');
            },

            'state _4 args expose whether the performer is already engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134_4,
                    'highDramaPlayerTurn_01134_4'
                );

                Assert::true($args['engaged'], 'engaged');
            },

            // WHY: Engage-to-draw is optional; ActionResolved + SorcererAbilityPlayed fire either way
            // so Elina (01118) can trigger (source comments).
            'engaging the performer queues engage, draw, ActionResolved, and Sorcerer played' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01134_4,
                    'highDramaPlayerTurn_01134_4',
                    1
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0]->sourceId, 'source');
                Assert::same(['engageChosen'], $world->game->gamestate->transitions, 'next');
            },

            'engaging an already-engaged performer throws' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01134_4,
                        'x',
                        1
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'already engaged');
            },

            'pass on engage still resolves Action and plays Sorcerer' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01134_4);

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0]->sourceId, 'source');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },
        ];
    }
}
