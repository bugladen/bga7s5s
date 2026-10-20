<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02033_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02033';
    }

    /**
     * @return array{0:_02033,1:Reaction_02033,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
        $mover = $world->placeCharacter(new GenericCharacter('Mover'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Reaction_02033 $reaction */
        $reaction = $rosa->getReactions()[0];
        return [$rosa, $reaction, $mover];
    }

    private function prop(string $name): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02033::class, $name);
        $prop->setAccessible(true);
        return $prop;
    }

    private function moved(TestWorld $world, int $cardId, string $abilityId = ''): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = Game::LOCATION_CITY_FORUM;
        $event->toLocation = Game::LOCATION_CITY_DOCKS;
        $event->abilityId = $abilityId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02033(), 'CardReaction');
            },

            'offers when a character moves to Rosa location' => function () {
                $world = new TestWorld();
                [$rosa, $reaction, $mover] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $mover->Id, 'ability-A'));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($rosa->ControllerId, $transitions[0]->playerId, 'rosa player');
                Assert::same($rosa->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($mover->Id, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'pending');
                Assert::same('ability-A', $this->prop('consumedAbilityId')->getValue($reaction), 'consumed');
            },

            'does not offer for Rosa herself, another location, or when she is not in the city' => function () {
                $world = new TestWorld();
                [$rosa, $reaction, $mover] = $this->scene($world);

                $self = $this->moved($world, $rosa->Id);
                $reaction->handleEvent($self);
                Assert::count(0, $world->theah->queuedEvents, 'self');

                $elsewhere = $this->moved($world, $mover->Id);
                $elsewhere->toLocation = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($elsewhere);
                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');

                $rosa->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->moved($world, $mover->Id));
                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            'skips an uncontrolled City Character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $city = new class extends CityCharacter {
                    public function __construct()
                    {
                        parent::__construct();
                        $this->Name = 'City';
                    }
                };
                $placed = $world->placeCharacter($city, Game::LOCATION_CITY_FORUM, 0);

                $reaction->handleEvent($this->moved($world, $placed->Id));
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled city');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->moved($world, $mover->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: One prompt per ability. Later moves with the same abilityId are swallowed
            // even after pending is cleared, until ActionResolved.
            'second move with the same abilityId does not offer again' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->moved($world, $mover->Id, 'blood-mark'));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $world->theah->queuedEvents = [];

                $reaction->handleEvent($this->moved($world, $other->Id, 'blood-mark'));
                Assert::count(0, $world->theah->queuedEvents, 'same ability');
                Assert::same(0, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'pending stayed clear');
            },

            'ActionResolved clears the consumed ability so a later use can offer' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $mover->Id, 'blood-mark'));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $reaction->handleEvent($resolved);
                Assert::same('', $this->prop('consumedAbilityId')->getValue($reaction), 'cleared');

                $world->theah->queuedEvents = [];
                $reaction->handleEvent($this->moved($world, $mover->Id, 'blood-mark'));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered again');
            },

            'an empty abilityId does not block a later move after pass' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $mover->Id, ''));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $world->theah->queuedEvents = [];

                $reaction->handleEvent($this->moved($world, $mover->Id, ''));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'does not stack a second offer while one mover is already pending' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->moved($world, $mover->Id, 'a'));
                $reaction->handleEvent($this->moved($world, $other->Id, 'b'));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'one');
                Assert::same($mover->Id, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'first mover');
            },

            // Dusk clears pending, awaiting, and consumed ability.
            'Dusk clears pending mover state' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $this->prop('awaitingMoverDiscard')->setValue($reaction, true);
                $this->prop('consumedAbilityId')->setValue($reaction, 'blood-mark');

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::same(0, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'pending');
                Assert::false($this->prop('awaitingMoverDiscard')->getValue($reaction), 'awaiting');
                Assert::same('', $this->prop('consumedAbilityId')->getValue($reaction), 'consumed');
            },

            'Rosa sees Have Them Discard when the mover has a hand, plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $world->game->activePlayerId = 1;

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('haveThemDiscard', $ids, true), 'discard');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'Rosa sees only Pass when the mover hand is empty' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $world->game->activePlayerId = 1;

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::false(in_array('haveThemDiscard', $ids, true), 'no discard');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'pass clears the pending mover' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same(0, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'cleared');
                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'haveThemDiscard hands the prompt to the mover controller' => function () {
                $world = new TestWorld();
                [$rosa, $reaction, $mover] = $this->scene($world);
                $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'haveThemDiscard');

                Assert::true($this->prop('awaitingMoverDiscard')->getValue($reaction), 'awaiting');
                $transitions = array_values(array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn ($t) => $t->transition === 'reaction'
                ));
                Assert::count(1, $transitions, 'their turn');
                Assert::same(2, $transitions[0]->playerId, 'mover controller');
                Assert::same($rosa->Id, $transitions[0]->sourceId, 'rosa source');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'haveThemDiscard with an empty hand ends without a second prompt' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'haveThemDiscard');

                Assert::same(0, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'cleared');
                Assert::false($this->prop('awaitingMoverDiscard')->getValue($reaction), 'not awaiting');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no prompt');
            },

            'mover discard buttons are the cards in their hand' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $card = $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $this->prop('awaitingMoverDiscard')->setValue($reaction, true);
                $world->game->activePlayerId = 2;

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['discardHand-' . $card->Id], $ids, 'hand');
            },

            'discarding the chosen hand card marks the reaction Used' => function () {
                $world = new TestWorld();
                [$rosa, $reaction, $mover] = $this->scene($world);
                $card = $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $this->prop('awaitingMoverDiscard')->setValue($reaction, true);
                $world->game->activePlayerId = 2;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'discardHand-' . $card->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($card->Id, $discards[0]->cardId, 'card');
                Assert::same(2, $discards[0]->ownerId, 'owner');
                Assert::same($rosa->Id, $discards[0]->sourceId, 'rosa');
                Assert::true($discards[0]->asEffect, 'effect');
                Assert::true($reaction->Used, 'used');
                Assert::same(0, $this->prop('pendingMoverCharacterId')->getValue($reaction), 'pending');
                Assert::false($this->prop('awaitingMoverDiscard')->getValue($reaction), 'awaiting');
            },

            'a discard click from the wrong player does not discard or clear state' => function () {
                $world = new TestWorld();
                [, $reaction, $mover] = $this->scene($world);
                $card = $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $this->prop('pendingMoverCharacterId')->setValue($reaction, $mover->Id);
                $this->prop('awaitingMoverDiscard')->setValue($reaction, true);
                $world->game->activePlayerId = 1;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'discardHand-' . $card->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
                Assert::false($reaction->Used, 'unused');
                Assert::true($this->prop('awaitingMoverDiscard')->getValue($reaction), 'still awaiting');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
