<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02030b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02030b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02030b';
    }

    /**
     * @return array{0:_02030,1:Reaction_02030b,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02030(), Game::LOCATION_HAND, 1);
        $character = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_02030b $reaction */
        $reaction = $risk->getReactions()[1];
        return [$risk, $reaction, $character];
    }

    private function musketeerProp(Reaction_02030b $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02030b::class, 'MusketeerCharacterId');
        $prop->setAccessible(true);
        return $prop;
    }

    private function issued(TestWorld $world, int $challengerId = 0, int $defenderId = 0): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02030b(), 'RiskReaction');
            },

            // Offers on EventChallengeIssued when in HAND.
            'offers on EventChallengeIssued when in hand with city character' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->issued($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'does not offer when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->issued($world));
                Assert::count(0, $world->theah->queuedEvents, 'not hand');
            },

            'does not offer without a city character' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->issued($world));
                Assert::count(0, $world->theah->queuedEvents, 'no city char');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->issued($world));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'performReaction sets MusketeerCharacterId and queues pay path' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "gainMusketeer-{$character->Id}"
                );

                Assert::same(
                    $character->Id,
                    $this->musketeerProp($reaction)->getValue($reaction),
                    'character id'
                );
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                $payTransitions = array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->transition === 'pay'
                );
                Assert::count(1, $payTransitions, 'pay transition');
                Assert::same($risk->Id, array_values($payTransitions)[0]->sourceId, 'source');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'EventRiskReactionTriggered grants Musketeer and sets Used' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $this->musketeerProp($reaction)->setValue($reaction, $character->Id);

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                Assert::true($character->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($reaction->Used, 'used');
            },

            // Same Musketeer grant/clear on ActionResolved (not PlayerTurnEnd — only ActionResolved).
            'EventActionResolved removes Musketeer and clears id' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->addTrait($world->game, 'Musketeer', true);
                $this->musketeerProp($reaction)->setValue($reaction, $character->Id);

                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $reaction->handleEvent($resolved);

                Assert::false($character->hasTrait('Musketeer'), 'removed');
                Assert::same(null, $this->musketeerProp($reaction)->getValue($reaction), 'cleared');
            },

            'EventPlayerTurnEnd does not clear Musketeer' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->addTrait($world->game, 'Musketeer', true);
                $this->musketeerProp($reaction)->setValue($reaction, $character->Id);

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                Assert::true($character->hasTrait('Musketeer'), 'kept');
                Assert::same($character->Id, $this->musketeerProp($reaction)->getValue($reaction), 'id kept');
            },
        ];
    }
}
