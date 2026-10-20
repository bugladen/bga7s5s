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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01185;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01185;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01185_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01185';
    }

    /**
     * Risky Undertaking in city; performer at location; ≥2 cards in hand.
     *
     * @return array{0:_01185,1:Action_01185,2:GenericCharacter,3:list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>}
     */
    private function scene(TestWorld $world, int $handExtras = 2): array
    {
        $card = $world->placeCard(new _01185(), Game::LOCATION_CITY_DOCKS, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $hand = [];
        for ($i = 0; $i < $handExtras; $i++) {
            $hand[] = $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
        }
        /** @var Action_01185 $action */
        $action = $card->getActions()[0];
        return [$card, $action, $performer, $hand];
    }

    public function tests(): array
    {
        return [
            'is an EventCityAction' => function () {
                Assert::instanceOf(EventCityAction::class, new Action_01185(), 'EventCityAction');
            },

            // WHY (journal 2026-04-09-01): availability = EventCityAction (character at
            // location) + ≥2 cards in hand. setUsed unnecessary — card is discarded.
            'available with a character at location and two cards in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with fewer than two cards in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'one card');
            },

            'unavailable with no character at the event location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no local character');
            },

            'trigger queues transition 01185' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01185', $transitions[0]->transition, 'name');
                Assert::same($card->Id, $transitions[0]->sourceId, 'source');
            },

            'args expose the event card id' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01185,
                    'highDramaPlayerTurn_01185'
                );

                Assert::same($card->Id, $args['id'], 'id');
            },

            // WHY (journal 2026-04-09-01): validate-then-execute two-loop — both hand cards
            // must pass ownership/location checks before either discard is queued.
            // Order: discard×2 → Renown → city discard (self) → ActionResolved.
            'act discards two hand cards, adds Renown, discards self, resolves' => function () {
                $world = new TestWorld();
                [$card, $action, , $hand] = $this->scene($world);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01185,
                    'highDramaPlayerTurn_01185',
                    [$hand[0]->Id, $hand[1]->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'two hand discards');
                Assert::same($hand[0]->Id, $discards[0]->cardId, 'first');
                Assert::same($hand[1]->Id, $discards[1]->cardId, 'second');
                Assert::true($discards[0]->AsPayment, 'as payment');
                Assert::same($card->Id, $discards[0]->sourceId, 'source');

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'at event location');
                Assert::same(1, $renown[0]->amount, 'one');

                $cityDiscard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $cityDiscard, 'self discard');
                Assert::same($card->Id, $cityDiscard[0]->cardId, 'Risky Undertaking');
                Assert::true($cityDiscard[0]->asEffect, 'as effect');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCardDiscardedFromHand::class, $order, true)
                        < array_search(EventRenownAddedToLocation::class, $order, true),
                    'discard before renown'
                );
                Assert::true(
                    array_search(EventRenownAddedToLocation::class, $order, true)
                        < array_search(EventCardAddedToCityDiscardPile::class, $order, true),
                    'renown before city discard'
                );
                Assert::true(
                    array_search(EventCardAddedToCityDiscardPile::class, $order, true)
                        < array_search(EventActionResolved::class, $order, true),
                    'discard before resolved'
                );
                Assert::same(['cardsDiscarded'], $world->game->gamestate->transitions, 'cardsDiscarded');
            },

            'refuses a wrong number of selected cards' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01185,
                        'x',
                        [$hand[0]->Id]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'count');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'refuses a card not in hand' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);
                $hand[0]->Location = Game::LOCATION_CITY_FORUM;

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01185,
                        'x',
                        [$hand[0]->Id, $hand[1]->Id]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'refuses a card owned by an opponent' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world, 1);
                $foeCard = $world->placeCard(new _01171(), Game::LOCATION_HAND, 2);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01185,
                        'x',
                        [$hand[0]->Id, $foeCard->Id]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'opponent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'refuses a missing card id' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world, 1);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01185,
                        'x',
                        [$hand[0]->Id, 99999]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
            },
        ];
    }
}
