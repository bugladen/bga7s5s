<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02039_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02039';
    }

    /**
     * @return array{0:_02039,1:Reaction_02039}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02039(), Game::LOCATION_HAND, 1);
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Reaction_02039 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction];
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02039(), 'RiskReaction');
            },

            // Opponent announces a combat card, risk is in hand, duel is underway.
            'opponent combat card announcement offers Add Threat' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $event = new EventCombatCardAnnounced();
                $event->playerId = 2;
                $event->cardId = 50;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'owner');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['addThreat', 'pass'], $ids, 'buttons');
            },

            'does not offer on your own combat card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $event = new EventCombatCardAnnounced();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'self');
            },

            'does not offer outside a duel or when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);

                $event = new EventCombatCardAnnounced();
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not duel');

                $world->game->globals->set(Game::IN_DUEL, true);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not hand');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $event = new EventCombatCardAnnounced();
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'addThreat enters the in-hand reaction pay state' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'addThreat');

                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'in hand reaction');
                Assert::same($reaction->Id, $pay[0]->internalId, 'reaction');
                $payTransitions = array_values(array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn ($t) => $t->transition === 'pay'
                ));
                Assert::count(1, $payTransitions, 'pay transition');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::false($reaction->Used, 'not used until paid');
            },

            'pass does not enter pay' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // Paid reaction adds one threat to both participants.
            'paid reaction adds one threat to both participants' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $event = new EventRiskReactionTriggered();
                $event->internalId = $reaction->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $threats = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threats, 'threat');
                Assert::same(1, $threats[0]->challengerThreat, 'challenger');
                Assert::same(1, $threats[0]->defenderThreat, 'defender');
                Assert::true($reaction->Used, 'used');
            },

            'ignores a different reaction trigger' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $event = new EventRiskReactionTriggered();
                $event->internalId = 'other';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'none');
                Assert::false($reaction->Used, 'unused');
            },
        ];
    }
}
