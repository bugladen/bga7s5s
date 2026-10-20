<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02060b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02060b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02060b';
    }

    /**
     * @return array{0:_02060,1:Reaction_02060b,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02060(), Game::LOCATION_HAND, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_02060b $reaction */
        $reaction = $risk->getReactions()[1];
        return [$risk, $reaction, $challenger, $defender];
    }

    private function duelEnd(TestWorld $world, int $challengingPlayerId, int $defendingPlayerId, int $challengerId, int $defenderId): EventDuelEnd
    {
        $event = new EventDuelEnd();
        $event->challengingPlayerId = $challengingPlayerId;
        $event->defendingPlayerId = $defendingPlayerId;
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
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
                Assert::instanceOf(RiskReaction::class, new Reaction_02060b(), 'RiskReaction');
            },

            // WHY: The offer keys off the duel player ids, not the character controllers.
            // Either participant already in discard or the locker suppresses the offer.
            'duel end offers when you challenged and both participants are still in play' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction');
                Assert::same($challenger->Id, $this->field($reaction, 'MyParticipantId'), 'your challenger');
                Assert::same($defender->Id, $this->field($reaction, 'OpponentId'), 'their defender');
            },

            'defending latches your defender as the participant' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->handleEvent($this->duelEnd($world, 2, 1, $challenger->Id, $defender->Id));
                Assert::same($defender->Id, $this->field($reaction, 'MyParticipantId'), 'your defender');
                Assert::same($challenger->Id, $this->field($reaction, 'OpponentId'), 'their challenger');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            'does not offer a spectator, a missing participant, a locker character, outside hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->handleEvent($this->duelEnd($world, 2, 2, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'neither player is you');

                $reaction->handleEvent($this->duelEnd($world, 1, 2, 999, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'missing challenger');

                $world->game->discardOrLockerByCardId[$defender->Id] = true;
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'defender already gone');
                unset($world->game->discardOrLockerByCardId[$defender->Id]);

                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'paying wounds your participant and the adversary still at that location' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundBoth');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');
                Assert::false($reaction->Used, 'not yet');

                $world->theah->takeQueuedEvents();
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'both');
                Assert::same($challenger->Id, $wounds[0]->characterId, 'you first');
                Assert::same($defender->Id, $wounds[1]->characterId, 'adversary');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::true($reaction->Used, 'used');
            },

            // WHY: "Opposing adversary" is checked at pay time. Your wound still happens
            // after they leave. Pass does not clear the latch; turn end does.
            'an adversary who left is not wounded, and turn end clears the latch' => function () {
                $world = new TestWorld();
                [, $reaction, $challenger, $defender] = $this->scene($world);
                $reaction->handleEvent($this->duelEnd($world, 1, 2, $challenger->Id, $defender->Id));
                $defender->Location = Game::LOCATION_CITY_FORUM;
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::same($challenger->Id, $this->field($reaction, 'MyParticipantId'), 'pass keeps the latch');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');

                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'only you');
                Assert::same($challenger->Id, $wounds[0]->characterId, 'participant');

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                Assert::same(0, $this->field($reaction, 'MyParticipantId'), 'cleared');
                Assert::same(0, $this->field($reaction, 'OpponentId'), 'opponent cleared');
            },
        ];
    }
}
