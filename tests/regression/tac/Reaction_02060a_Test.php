<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060a;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02060a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02060a';
    }

    /**
     * @return array{0:_02060,1:Reaction_02060a,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02060(), Game::LOCATION_HAND, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $target = $world->placeCharacter(new GenericCharacter('Refuser'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_02060a $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $challenger, $target];
    }

    private function rejected(TestWorld $world, int $challengerId, int $targetId): EventChallengeRejected
    {
        $event = new EventChallengeRejected();
        $event->challengerId = $challengerId;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function field(object $object, string $name): mixed
    {
        $property = new \ReflectionProperty($object, $name);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02060a(), 'RiskReaction');
            },

            'a refused challenge from your character offers the reaction' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $target] = $this->scene($world);
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction');
                Assert::same(
                    ['woundBoth', 'pass'],
                    array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'),
                    'buttons'
                );
                Assert::same($challenger->Id, $this->field($reaction, 'ChallengerId'), 'latched challenger');
                Assert::same($target->Id, $this->field($reaction, 'TargetId'), 'latched target');
            },

            'does not offer for an opponent challenger, outside the hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $target] = $this->scene($world);
                $challenger->ControllerId = 2;
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not your challenger');

                $challenger->ControllerId = 1;
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'wound both enters pay, and paying wounds the challenger then the refuser' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $target] = $this->scene($world);
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundBoth');

                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'type');
                Assert::false($reaction->Used, 'not yet');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');

                $world->theah->takeQueuedEvents();
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);

                Assert::true($reaction->Used, 'used');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'both');
                Assert::same($challenger->Id, $wounds[0]->characterId, 'challenger first');
                Assert::same($target->Id, $wounds[1]->characterId, 'refuser second');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
            },

            // WHY: Pass does not clear the latched characters. Turn end does.
            'pass leaves the latch, and turn end clears it' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $target] = $this->scene($world);
                $reaction->handleEvent($this->rejected($world, $challenger->Id, $target->Id));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'unused');
                Assert::same($challenger->Id, $this->field($reaction, 'ChallengerId'), 'still latched');

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                Assert::same(0, $this->field($reaction, 'ChallengerId'), 'challenger cleared');
                Assert::same(0, $this->field($reaction, 'TargetId'), 'target cleared');
            },
        ];
    }
}
