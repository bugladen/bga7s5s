<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01182;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01182;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01182_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01182';
    }

    /**
     * Controlled Eko at Docks; opposing character leaving that location.
     *
     * @return array{0:_01182,1:Reaction_01182,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $eko = $world->placeCharacter(new _01182(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_01182 $reaction */
        $reaction = $eko->getReactions()[0];
        return [$eko, $reaction, $foe];
    }

    private function moving(
        TestWorld $world,
        int $cardId,
        string $from = Game::LOCATION_CITY_DOCKS,
        string $to = Game::LOCATION_CITY_FORUM
    ): EventCardMoving {
        $event = new EventCardMoving();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01182(), 'CardReaction');
            },

            // WHY (journal 2026-04-23-01): listen on EventCardMoving; TargetCharacterIds
            // (array) so multiple dusk leavers do not overwrite the prompt target.
            // WHY (journal 2026-04-08-03): wound is a side effect, not a cancel/re-queue —
            // "Before" in the text is flavor; EventCardMoving is the engine hook used here.
            'offers when an opposing character moves from Eko\'s city location' => function () {
                $world = new TestWorld();
                [$eko, $reaction, $foe] = $this->scene($world);

                $reaction->handleEvent($this->moving($world, $foe->Id));

                Assert::same([$foe->Id], $reaction->TargetCharacterIds, 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($eko->Id, $transitions[0]->sourceId, 'Eko');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer for a friendly character leaving' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->moving($world, $ally->Id));

                Assert::count(0, $world->theah->queuedEvents, 'ally');
            },

            'does not offer when the mover leaves a different location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Forum Foe'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->moving(
                    $world,
                    $foe->Id,
                    Game::LOCATION_CITY_FORUM,
                    Game::LOCATION_CITY_BAZAAR
                ));

                Assert::count(0, $world->theah->queuedEvents, 'wrong from');
            },

            'does not offer when Eko is not in the city' => function () {
                $world = new TestWorld();
                [$eko, $reaction, $foe] = $this->scene($world);
                $eko->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->moving(
                    $world,
                    $foe->Id,
                    Game::LOCATION_PLAYER_HOME,
                    Game::LOCATION_CITY_DOCKS
                ));

                Assert::count(0, $world->theah->queuedEvents, 'not in city');
            },

            'does not offer when Eko is uncontrolled' => function () {
                $world = new TestWorld();
                [$eko, $reaction, $foe] = $this->scene($world);
                $eko->ControllerId = 0;

                $reaction->handleEvent($this->moving($world, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->moving($world, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer Wound Character and Pass' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $foe->Id));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['woundCharacter', 'pass'], $ids, 'buttons');
            },

            // WHY (journal 2026-04-23-01): wound clears remaining targets + deleteTransitionEvents
            // because Used makes later queued offers dead but they already sit in the queue.
            'wound queues BeingWounded, clears targets, and marks Used' => function () {
                $world = new TestWorld();
                [$eko, $reaction, $foe] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, $foe->Id));
                $second = $world->placeCharacter(new GenericCharacter('Second'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->moving($world, $second->Id));
                Assert::count(2, $reaction->TargetCharacterIds, 'two pending');
                Assert::count(2, $world->theah->queuedOfType(EventTransition::class), 'two offers');
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundCharacter');

                Assert::true($reaction->Used, 'used');
                Assert::false($reaction->isAvailable(), 'unavailable');
                Assert::same([], $reaction->TargetCharacterIds, 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'transitions deleted');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'first target');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($eko->Id, $wounds[0]->sourceId, 'Eko');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY (journal 2026-04-08-03 / 2026-04-23-01): pass does not set Used so a later
            // leaver can still be wounded; only shift the current target.
            'pass shifts the current target without marking Used' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $second = $world->placeCharacter(new GenericCharacter('Second'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->moving($world, $foe->Id));
                $reaction->handleEvent($this->moving($world, $second->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::true($reaction->isAvailable(), 'available');
                Assert::same([$second->Id], $reaction->TargetCharacterIds, 'second remains');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
