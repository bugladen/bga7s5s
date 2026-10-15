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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02030a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02030a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02030a';
    }

    /**
     * @return array{0:_02030,1:Reaction_02030a,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02030(), Game::LOCATION_HAND, 1);
        $character = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_02030a $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $character];
    }

    private function musketeerProp(Reaction_02030a $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02030a::class, 'MusketeerCharacterId');
        $prop->setAccessible(true);
        return $prop;
    }

    private function actionActivated(TestWorld $world, int $sourceId = 999): EventActionActivated
    {
        $event = new EventActionActivated();
        $event->playerId = 2;
        $event->sourceId = $sourceId;
        $event->actionId = 'someAction';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02030a(), 'RiskReaction');
            },

            // Offers on Action/Reaction/Technique/Maneuver Activated when risk in HAND and city characters.
            'offers on EventActionActivated when in hand with city character' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->actionActivated($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
            },

            'offers on Technique, Maneuver, and Reaction Activated' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                foreach ([
                    new EventTechniqueActivated(),
                    new EventManeuverActivated(),
                    new EventReactionActivated(),
                ] as $event) {
                    $world->theah->queuedEvents = [];
                    $event->theah = $world->theah;
                    if ($event instanceof EventReactionActivated) {
                        $event->sourceId = 999;
                    }
                    $reaction->handleEvent($event);
                    Assert::count(1, $world->theah->queuedOfType(EventTransition::class), get_class($event));
                }
            },

            // WHY: skip when EventReactionActivated sourceId == own card (don't react to self).
            'does not offer on own ReactionActivated' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $event = new EventReactionActivated();
                $event->sourceId = $risk->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'self skip');
            },

            'does not offer when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->actionActivated($world));
                Assert::count(0, $world->theah->queuedEvents, 'not hand');
            },

            'does not offer without a city character' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->actionActivated($world));
                Assert::count(0, $world->theah->queuedEvents, 'no city char');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->actionActivated($world));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // performReaction → pay state path; MusketeerCharacterId private via Reflection.
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

            'pass does not set MusketeerCharacterId or queue pay' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same(null, $this->musketeerProp($reaction)->getValue($reaction), 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },

            // EventRiskReactionTriggered adds Musketeer.
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

            // Clears on ActionResolved or PlayerTurnEnd.
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

            'EventPlayerTurnEnd removes Musketeer and clears id' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->addTrait($world->game, 'Musketeer', true);
                $this->musketeerProp($reaction)->setValue($reaction, $character->Id);

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                Assert::false($character->hasTrait('Musketeer'), 'removed');
                Assert::same(null, $this->musketeerProp($reaction)->getValue($reaction), 'cleared');
            },
        ];
    }
}
