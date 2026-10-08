<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_01077_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01077';
    }

    /**
     * @param list<string> $actorTraits
     * @return array{0:_01077,1:Maneuver_01077,2:Character,3:Character}
     */
    private function duel(TestWorld $world, array $actorTraits = ['Duelist'], bool $inDuel = true): array
    {
        $risk = $world->placeCard(new _01077(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, $inDuel);

        /** @var Maneuver_01077 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    /**
     * Seed the top of the player's Faction Deck with `$count` cards that exist only in the "DB"
     * (registerDbCard), exactly like undrawn deck cards in the real game.
     *
     * @return list<GenericCharacter>
     */
    private function seedDeck(TestWorld $world, int $count): array
    {
        $cards = [];
        $top = [];
        for ($i = 1; $i <= $count; $i++) {
            $card = new GenericCharacter("Deck {$i}");
            $card->setId($world->nextId());
            $card->ControllerId = 1;
            $card->OwnerId = 1;
            $card->Location = 'Deck-1';
            $world->game->registerDbCard($card);
            $cards[] = $card;
            $top[] = ['id' => $card->Id];
        }
        $world->game->topFactionCards = $top;
        return $cards;
    }

    public function tests(): array
    {
        return [
            'available in a duel when the round actor is a Duelist' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Duelist'], false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable when the round actor is not a Duelist' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, ['Brute']);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a Duelist');
            },

            'resolve queues transition 01077' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->adversaryId = $foe->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01077', $transitions[0]->transition, 'name');
                Assert::same($maneuver->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'player');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            // WHY: reveal count is the participant's modified Finesse, not the printed stat.
            'args reveal exactly ModifiedFinesse cards from the top of the Faction Deck' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 2;
                $deck = $this->seedDeck($world, 4);

                $args = $maneuver->getArgsFromManeuver($world->game, States::DUEL_RESOLVE_MANEUVER_01077, 'x');

                Assert::count(2, $args['cards'], 'two revealed');
                Assert::same($deck[0]->Id, $args['cards'][0]['id'], 'first');
                Assert::same($deck[1]->Id, $args['cards'][1]['id'], 'second');
            },

            'step 2 plays the chosen card and sinks every other revealed card to the bottom' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 3;
                $deck = $this->seedDeck($world, 5);
                $chosen = $deck[1];

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01077_2, 'x', $chosen->Id);

                // Unchosen revealed cards (top 3 minus chosen) are sunk; card #4/#5 were never revealed.
                Assert::same([
                    ['id' => $deck[0]->Id, 'deck' => 'Deck-1', 'onTop' => false],
                    ['id' => $deck[2]->Id, 'deck' => 'Deck-1', 'onTop' => false],
                ], $world->game->deckInserts, 'sunk (onTop=false) to the actor\'s faction deck');
            },

            // WHY: the card is played straight from the Faction Deck by stSetNextCombatCard. It must NOT be
            // staged in hand first - the old play path that moved it out of hand is gone, and staging left the
            // client's hand stock holding a card the server had already moved on.
            'step 2 parks the chosen card (never staged in hand) and arms NEXT_COMBAT_CARD + ABNORMAL_FLOW' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 2;
                $deck = $this->seedDeck($world, 3);
                $chosen = $deck[0];

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01077_2, 'x', $chosen->Id);

                Assert::same([$chosen->Id], $world->game->parkedCardIds, 'parked out of the deck');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal flow');
                Assert::same($chosen->Id, $world->game->globals->get(Game::NEXT_COMBAT_CARD), 'next combat card');
                Assert::same('Deck-1', $chosen->Location, 'not moved into hand by this step');
                Assert::false($chosen->Location === Game::LOCATION_HAND, 'never staged in hand');
                Assert::same($chosen, $world->theah->getCardById($chosen->Id), 'added to the world');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
                Assert::count(0, $world->theah->queuedEvents, 'no hand/move events queued');
            },

            'step 2 with Finesse 1 sinks nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 1;
                $deck = $this->seedDeck($world, 3);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01077_2, 'x', $deck[0]->Id);

                Assert::count(0, $world->game->deckInserts, 'only one revealed, so nothing to sink');
                Assert::same($deck[0]->Id, $world->game->globals->get(Game::NEXT_COMBAT_CARD), 'chosen');
            },

            'step 2 refuses a card outside the revealed top cards' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 2;
                $deck = $this->seedDeck($world, 4);

                $threw = false;
                try {
                    // deck[2] is third from top; only two are revealed
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01077_2, 'x', $deck[2]->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->game->deckInserts, 'nothing sunk');
                Assert::count(0, $world->game->parkedCardIds, 'nothing parked');
                Assert::same(null, $world->game->globals->get(Game::NEXT_COMBAT_CARD), 'next combat card untouched');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 1 state (reveal view) does not choose or sink anything' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $actor->ModifiedFinesse = 2;
                $deck = $this->seedDeck($world, 3);

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_01077, 'x', $deck[0]->Id);

                Assert::count(0, $world->game->deckInserts, 'no sink');
                Assert::count(0, $world->game->parkedCardIds, 'no park');
                Assert::same(null, $world->game->globals->get(Game::NEXT_COMBAT_CARD), 'no next combat card');
            },
        ];
    }
}
