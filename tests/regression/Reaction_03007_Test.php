<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03007_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03007';
    }

    /**
     * @return array{0:_03007,1:Reaction_03007,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $strega = $world->placeCharacter(new GenericCharacter('Strand', ['Strega']), Game::LOCATION_CITY_DOCKS, 1);
        $shears = $world->placeCard(new _03007(), Game::LOCATION_CITY_DOCKS, 1);
        $shears->AttachedToId = $strega->Id;
        $victim = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03007 $reaction */
        $reaction = $shears->getReactions()[0];
        return [$shears, $reaction, $strega, $victim];
    }

    private function destroyed(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->theah = $world->theah;
        $event->characterId = $characterId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a sorcerer attachment reaction' => function () {
                $reaction = new Reaction_03007();
                Assert::instanceOf(AttachmentReaction::class, $reaction, 'attachment reaction');
                Assert::instanceOf(ISorcererAbility::class, $reaction, 'sorcery');
            },

            'offers when an opposing non-Brute at the equipped Strega location is destroyed' => function () {
                $world = new TestWorld();
                [$shears, $reaction, , $victim] = $this->scene($world);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same(1, $transitions[0]->playerId, 'owner');
                Assert::same($shears->Id, $transitions[0]->sourceId, 'attachment');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['engage', 'pass'], $buttons, 'buttons');
            },

            'a Brute, another location, Home, an engaged card, or your own character does not offer' => function () {
                $world = new TestWorld();
                [$shears, $reaction, $strega, $victim] = $this->scene($world);
                $brute = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->destroyed($world, $brute->Id));
                Assert::count(0, $world->theah->queuedEvents, 'brute goes to discard');

                $victim->Location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'different location');
                $victim->Location = Game::LOCATION_CITY_DOCKS;

                $strega->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'city reaction');
                $strega->Location = Game::LOCATION_CITY_DOCKS;

                $shears->Engaged = true;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'engaged');
                $shears->Engaged = false;

                $shears->AttachedToId = 0;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'unattached');
                $shears->AttachedToId = $strega->Id;

                $victim->ControllerId = 1;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not opposing');

                $victim->ControllerId = 0;
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'anything other than engage on the offer is a pass and does not spend' => function () {
                $world = new TestWorld();
                [, $reaction, , $victim] = $this->scene($world);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'not engaged');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::false(in_array('engage', $buttons, true), 'latch cleared');
            },

            'engage with two cards in hand sends the opponent the choice' => function () {
                $world = new TestWorld();
                [$shears, $reaction, $strega, $victim] = $this->scene($world);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class);
                Assert::same($shears->Id, $engage[0]->cardId, 'engage the shears');
                Assert::same($reaction->Id, $start[0]->abilityId, 'ability');
                Assert::same($strega->Id, $start[0]->performerId, 'strega performer');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(2, $transitions[0]->playerId, 'opponent chooses');
                Assert::false($reaction->Used, 'not finished');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('sink', $buttons, true), 'sink');
                Assert::false(in_array('wound', $buttons, true), 'no Leader, so no wound button');
            },

            'fewer than two cards wounds the Leader immediately when one exists' => function () {
                $world = new TestWorld();
                [$shears, $reaction, , $victim] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_PLAYER_HOME, 2);
                $world->theah->leadersByPlayerId[2] = $leader;
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::same($leader->Id, $wound[0]->characterId, 'leader');
                Assert::same($shears->Id, $wound[0]->sourceId, 'source');
                Assert::same($reaction->Id, $played[0]->abilityId, 'played');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice');
            },

            'wound choice and a short hand during sink both wound the Leader' => function () {
                $world = new TestWorld();
                [$shears, $reaction, , $victim] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_PLAYER_HOME, 2);
                $world->theah->leadersByPlayerId[2] = $leader;
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'wound');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($leader->Id, $wound[0]->characterId, 'wound choice');
                Assert::true($reaction->Used, 'spent');
                unset($shears);
            },

            'sinking two hand cards finishes without wounding' => function () {
                $world = new TestWorld();
                [, $reaction, , $victim] = $this->scene($world);
                $first = $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $second = $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "card-{$first->Id}");
                $reaction->performReaction($world->game, 0, $reaction->Id, "card-{$second->Id}");
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::same($world->game->getPlayerFactionDeckName(2), $first->Location, 'first sunk');
                Assert::same($world->game->getPlayerFactionDeckName(2), $second->Location, 'second sunk');
            },

            'a card outside the hand throws' => function () {
                $world = new TestWorld();
                [, $reaction, , $victim] = $this->scene($world);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->destroyed($world, $victim->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'sink');
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'card-999');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
            },

            'beginCopy skips the engage and still opens the choice' => function () {
                $world = new TestWorld();
                [$shears, $reaction, $strega] = $this->scene($world);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _02061(), Game::LOCATION_HAND, 2);
                $reaction->beginCopy($world->game, 2);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class);
                Assert::same($shears->Id, $start[0]->sourceId, 'source');
                Assert::same($strega->Id, $start[0]->performerId, 'performer');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(2, $transitions[0]->playerId, 'opponent');
                Assert::false($reaction->Used, 'choice still open');
            },
        ];
    }
}
