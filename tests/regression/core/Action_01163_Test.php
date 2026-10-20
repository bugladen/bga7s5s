<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01163;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01163_CardClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01164;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01163;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01163_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01163';
    }

    /**
     * Devotion in hand; three faction-deck cards stubbed as top-of-deck.
     *
     * @return array{0:_01163,1:Action_01163,2:_01162,3:_01164,4:_01165}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01163(), Game::LOCATION_HAND, 1);
        $a = $world->placeCard(new _01162(), 'Deck-1', 1);
        $b = $world->placeCard(new _01164(), 'Deck-1', 1);
        $c = $world->placeCard(new _01165(), 'Deck-1', 1);
        $world->game->topFactionCards = [
            ['id' => $a->Id],
            ['id' => $b->Id],
            ['id' => $c->Id],
        ];
        /** @var Action_01163 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $a, $b, $c];
    }

    public function tests(): array
    {
        return [
            'trigger stores top 3 deck ids and queues transition 01163' => function () {
                $world = new TestWorld();
                [$risk, $action, $a, $b, $c] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(
                    [$a->Id, $b->Id, $c->Id],
                    $world->game->globals->get(Game::REVEALED_CARDS),
                    'top 3'
                );
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01163', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args expose remaining revealed cards as property arrays' => function () {
                $world = new TestWorld();
                [, $action, $a, $b] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$a->Id, $b->Id]);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01163,
                    'highDramaPlayerTurn_01163'
                );

                Assert::count(2, $args['cards'], 'two');
                Assert::same($a->Id, $args['cards'][0]['id'], 'first');
                Assert::same($b->Id, $args['cards'][1]['id'], 'second');
            },

            'step 1 sinks chosen card to bottom of faction deck' => function () {
                $world = new TestWorld();
                [, $action, $a, $b, $c] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$a->Id, $b->Id, $c->Id]);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01163,
                    'highDramaPlayerTurn_01163',
                    $a->Id
                );

                Assert::same([
                    ['id' => $a->Id, 'deck' => 'Deck-1', 'onTop' => false],
                ], $world->game->deckInserts, 'sink bottom');
                Assert::same([$b->Id, $c->Id], array_values($world->game->globals->get(Game::REVEALED_CARDS)), 'remaining');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 1 refuses a card not in the faction deck' => function () {
                $world = new TestWorld();
                [, $action, $a, $b, $c] = $this->scene($world);
                $hand = $world->placeCard(new _01162(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::REVEALED_CARDS, [$a->Id, $b->Id, $c->Id]);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01163,
                        'x',
                        $hand->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in deck');
                Assert::count(0, $world->game->deckInserts, 'no sink');
            },

            'step 2 queues CardAddedToHand for the chosen card' => function () {
                $world = new TestWorld();
                [, $action, , $b, $c] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$b->Id, $c->Id]);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01163_2,
                    'highDramaPlayerTurn_01163_2',
                    $b->Id
                );

                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $added, 'hand');
                Assert::same($b->Id, $added[0]->cardId, 'chosen');
                Assert::same([$c->Id], array_values($world->game->globals->get(Game::REVEALED_CARDS)), 'last remains');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 3 args list city location names' => function () {
                $world = new TestWorld();
                [, $action, , , $c] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$c->Id]);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01163_3,
                    'highDramaPlayerTurn_01163_3'
                );

                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'Docks');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::count(1, $args['cards'], 'last card still shown');
            },

            // WHY (journal 2026-03-31-05): original → PERMANENTLY_HIDDEN; facedown clone
            // mustered at the location with ClonedCardId/ParentCardId for dusk resolve.
            'step 3 hides original, musters facedown clone, and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, , , $c] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$c->Id]);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01163_3,
                    'highDramaPlayerTurn_01163_3',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $c->Location, 'original hidden');

                $clones = array_filter(
                    $world->game->createdCardsInLocation,
                    fn($card) => $card instanceof _01163_CardClone
                );
                Assert::count(1, $clones, 'one clone');
                /** @var _01163_CardClone $clone */
                $clone = array_values($clones)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $clone->Location, 'at Docks');
                Assert::true($clone->FaceDown, 'facedown');
                Assert::same($c->Id, $clone->ClonedCardId, 'cloned');
                Assert::same($risk->Id, $clone->ParentCardId, 'parent');
                Assert::same($c->Name, $clone->Name, 'name copied');

                $mustered = $world->theah->queuedOfType(EventCardMustered::class);
                Assert::count(1, $mustered, 'muster');
                Assert::same($clone->Id, $mustered[0]->cardId, 'clone mustered');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 3 refuses a non-city location name' => function () {
                $world = new TestWorld();
                [, $action, , , $c] = $this->scene($world);
                $world->game->globals->set(Game::REVEALED_CARDS, [$c->Id]);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01163_3,
                        'x',
                        [Game::LOCATION_PLAYER_HOME]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Home refused');
                Assert::count(0, $world->game->createdCardsInLocation, 'no clone');
            },

            'state constants registered' => function () {
                Assert::same(401163, States::HIGH_DRAMA_PLAYER_TURN_01163, '01163');
                Assert::same(4011632, States::HIGH_DRAMA_PLAYER_TURN_01163_2, '01163_2');
                Assert::same(4011633, States::HIGH_DRAMA_PLAYER_TURN_01163_3, '01163_3');
            },
        ];
    }
}
