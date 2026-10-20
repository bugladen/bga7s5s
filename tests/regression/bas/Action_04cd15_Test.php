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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd15;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd15;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd15_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd15';
    }

    /**
     * @return array{0:_04cd15,1:Action_04cd15,2:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $box = $world->placeCard(new _04cd15(), Game::LOCATION_CITY_DOCKS, 1);
        $box->AttachedToId = $host->Id;
        /** @var Action_04cd15 $action */
        $action = $box->getActions()[0];
        return [$box, $action, $host];
    }

    /**
     * @return list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>
     */
    private function seedDeck(TestWorld $world, int $count): array
    {
        $deck = $world->game->getPlayerFactionDeckName(1);
        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $card = $world->placeCard(new _01028(), $deck, 1);
            $cards[] = $card;
            $world->game->topFactionCards[] = ['id' => $card->Id];
        }
        return $cards;
    }

    public function tests(): array
    {
        return [
            'available while equipped, unengaged, and in the city, even with an empty deck' => function () {
                $world = new TestWorld();
                [$box, $action, $host] = $this->equipped($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'empty deck');

                $box->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $box->Engaged = false;

                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $host->Location = Game::LOCATION_CITY_DOCKS;

                $box->AttachedToId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unequipped');
                $box->AttachedToId = $host->Id;

                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used stays used');
                Assert::true($action->Used, 'not cleared');
            },

            'trigger engages the attachment and skips the look when the deck is empty' => function () {
                $world = new TestWorld();
                [$box, $action] = $this->equipped($world);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                Assert::same($box->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::same('04cd15_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'discard step');
                Assert::same('[]', $world->game->globals->get(Game::CHOSEN_CARD), 'nothing looked');
            },

            'trigger with cards opens the sink step and remembers them' => function () {
                $world = new TestWorld();
                [$box, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 3);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                Assert::same('04cd15', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'sink');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15, '');
                Assert::same($cards[0]->Id, $args['cards'][0]->id, 'first looked');
                Assert::count(3, $args['cards'], 'three');
            },

            // WHY: Every chosen id is validated before any insert. A bad id sinks nothing.
            'a card that was not looked at, or is not in the deck, is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    ['id' => $cards[0]->Id],
                    ['id' => $cards[1]->Id],
                ]));

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15, '', [999]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not looked at');
                Assert::count(0, $world->game->deckInserts, 'no insert');

                $cards[0]->Location = Game::LOCATION_HAND;
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15, '', [$cards[0]->Id]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'left the deck');
                Assert::count(0, $world->game->deckInserts, 'still no insert');
            },

            // WHY: Sinks are immediate bottom inserts. Zero left goes to the discard choice.
            // One left is put back on top. Two or more open reorder and are not inserted yet.
            'sinking updates the remainder and chooses the next step' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 3);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    ['id' => $cards[0]->Id],
                    ['id' => $cards[1]->Id],
                    ['id' => $cards[2]->Id],
                ]));

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15, '', [$cards[0]->Id]);
                Assert::same($cards[0]->Id, $world->game->deckInserts[0]['id'], 'sunk');
                Assert::false($world->game->deckInserts[0]['onTop'], 'bottom');
                Assert::same('Deck-1', $world->game->deckInserts[0]['deck'], 'faction deck');
                Assert::same('reorder', $world->game->gamestate->transitions[0], 'two remain');
                Assert::count(1, $world->game->deckInserts, 'remainder not inserted');

                $world->game->deckInserts = [];
                $world->game->gamestate->transitions = [];
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([['id' => $cards[1]->Id]]));
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15, '', [$cards[1]->Id]);
                Assert::count(1, $world->game->deckInserts, 'only the sink');
                Assert::same('discardChoice', $world->game->gamestate->transitions[0], 'none remain');
            },

            'passing the sink keeps every looked card and reorders when two or more remain' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    ['id' => $cards[0]->Id],
                    ['id' => $cards[1]->Id],
                ]));
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15);
                Assert::count(0, $world->game->deckInserts, 'none sunk');
                Assert::same('reorder', $world->game->gamestate->transitions[0], 'reorder');

                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([['id' => $cards[0]->Id]]));
                $world->game->gamestate->transitions = [];
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15);
                Assert::true($world->game->deckInserts[0]['onTop'], 'single card replaced on top');
                Assert::same('discardChoice', $world->game->gamestate->transitions[0], 'then discard');
            },

            'reorder requires every remaining card, and a later unknown id still inserts earlier ones' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    ['id' => $cards[0]->Id],
                    ['id' => $cards[1]->Id],
                ]));

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15_2, '', [$cards[0]->Id]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'short list');
                Assert::count(0, $world->game->deckInserts, 'rejected before inserts');

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_04CD15_2,
                        '',
                        [$cards[0]->Id, 999]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'unknown');
                Assert::same($cards[0]->Id, $world->game->deckInserts[0]['id'], 'earlier id already inserted');
                Assert::true($world->game->deckInserts[0]['onTop'], 'top');
            },

            'a full reorder inserts each card on top and advances' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $cards = $this->seedDeck($world, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    ['id' => $cards[0]->Id],
                    ['id' => $cards[1]->Id],
                ]));
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04CD15_2,
                    '',
                    [$cards[1]->Id, $cards[0]->Id]
                );
                Assert::same([$cards[1]->Id, $cards[0]->Id], array_column($world->game->deckInserts, 'id'), 'order');
                Assert::same('cardsSorted', $world->game->gamestate->transitions[0], 'transition');
            },

            'discard to draw requires the controller hand, then resolves' => function () {
                $world = new TestWorld();
                [$box, $action] = $this->equipped($world);
                $theirs = $world->placeCard(new _01028(), Game::LOCATION_HAND, 2);
                $mine = $world->placeCard(new _01028(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15_3, '', $theirs->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not your hand');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15_3, '', $mine->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::same($mine->Id, $discard->cardId, 'discarded');
                Assert::true($discard->asEffect, 'effect');
                Assert::same($box->Id, $discard->sourceId, 'box');
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'draw');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'resolved');
                Assert::same('discardChosen', $world->game->gamestate->transitions[0], 'transition');
            },

            'passing the discard resolves without a draw' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD15_3);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('pass', $world->game->gamestate->transitions[0], 'transition');
            },
        ];
    }
}
