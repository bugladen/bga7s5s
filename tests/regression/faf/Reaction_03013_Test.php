<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IPayTimeCostDiscount;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03013_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03013';
    }

    /**
     * @return array{0:_03013,1:Reaction_03013,2:_03012}
     */
    private function scene(TestWorld $world): array
    {
        $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
        $risk = $world->placeCard(new _03012(), Game::LOCATION_HAND, 1);
        $risk->WealthCost = 1;
        /** @var Reaction_03013 $reaction */
        $reaction = $daniella->getReactions()[0];
        return [$daniella, $reaction, $risk];
    }

    private function paying(TestWorld $world, int $cardId, string $internalId, int $playerId = 1): EventEnteringPayState
    {
        $event = new EventEnteringPayState();
        $event->theah = $world->theah;
        $event->playerId = $playerId;
        $event->cardId = $cardId;
        $event->internalId = $internalId;
        $event->payStateType = Game::PAY_STATE_IN_HAND_REACTION;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a pay-time discount reaction' => function () {
                $reaction = new Reaction_03013();
                Assert::instanceOf(CardReaction::class, $reaction, 'card reaction');
                Assert::instanceOf(IPayTimeCostDiscount::class, $reaction, 'discount');
                Assert::false($reaction->isDiscountActive(), 'starts idle');
            },

            // WHY: Only your Faith or Sorcery card with a printed cost above zero.
            // The offer is stacked so it runs before the pay events already queued.
            'offers while you pay for a Sorcery or Faith card' => function () {
                $world = new TestWorld();
                [$daniella, $reaction, $risk] = $this->scene($world);
                $reaction->handleEvent($this->paying($world, $risk->Id, $risk->getReactions()[0]->Id));
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same($daniella->Id, $transition->sourceId, 'daniella');
                Assert::same($reaction->Id, $transition->internalId, 'this reaction');

                $world->theah->takeQueuedEvents();
                $risk->WealthCost = 0;
                $reaction->handleEvent($this->paying($world, $risk->Id, $risk->getReactions()[0]->Id));
                Assert::count(0, $world->theah->queuedEvents, 'free card');
                $risk->WealthCost = 1;

                $plain = $world->placeCard(new _03019(), Game::LOCATION_HAND, 1);
                $reaction->handleEvent($this->paying($world, $plain->Id, 'x'));
                Assert::count(0, $world->theah->queuedEvents, 'no Faith or Sorcery');

                $reaction->handleEvent($this->paying($world, $risk->Id, $risk->getReactions()[0]->Id, 2));
                Assert::count(0, $world->theah->queuedEvents, 'opponent paying');
            },

            'activate discounts that card by 1 until the turn, the action, or the round ends' => function () {
                $world = new TestWorld();
                [$daniella, $reaction, $risk] = $this->scene($world);
                $paid = $risk->getReactions()[0];
                $reaction->handleEvent($this->paying($world, $risk->Id, $paid->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
                Assert::true($reaction->Used, 'spent');
                Assert::true($reaction->isDiscountActive(), 'active');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal flow');

                $explanations = [];
                Assert::same(1, $reaction->getReactionFromHandDiscount($world->theah, $paid, $explanations), 'reaction discount');

                $action = new Action_03011();
                $action->setOwnerId($risk->Id);
                Assert::same(1, $reaction->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'action on that card');

                $other = new Action_03011();
                $other->setOwnerId($daniella->Id);
                Assert::same(0, $reaction->getActionFromHandDiscount($world->theah, null, $other, $explanations), 'different card');

                $attachment = $world->placeCard(new _03019(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new \Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same(0, $reaction->getEquipDiscount($world->theah, $performer, $attachment, $explanations), 'not the discounted id yet');
                $reaction->retargetDiscountedCard($attachment->Id);
                Assert::same(1, $reaction->getEquipDiscount($world->theah, $performer, $attachment, $explanations), 'retargeted equip');

                $world->theah->duelActor = $performer;
                Assert::same(1, $reaction->getManeuverFromCombatCardDiscount($world->theah, $attachment, $explanations), 'same id in a duel');

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                Assert::false($reaction->isDiscountActive(), 'turn end');
                Assert::same(0, $reaction->getReactionFromHandDiscount($world->theah, $paid, $explanations), 'discount gone');

                // WHY: Activate sets Used, and the clear above also zeroes PayStateType.
                // A later offer only restamps the pay type while the reaction is available.
                $reaction->Used = false;
                $reaction->handleEvent($this->paying($world, $risk->Id, $paid->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $reaction->handleEvent($resolved);
                Assert::false($reaction->isDiscountActive(), 'action resolved');

                $reaction->Used = false;
                $reaction->handleEvent($this->paying($world, $risk->Id, $paid->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
                $round = new EventDuelEndOfRound();
                $round->theah = $world->theah;
                $reaction->handleEvent($round);
                Assert::false($reaction->isDiscountActive(), 'end of round');
                unset($action, $other);
            },

            'pass does not arm the discount' => function () {
                $world = new TestWorld();
                [, $reaction, $risk] = $this->scene($world);
                $reaction->handleEvent($this->paying($world, $risk->Id, $risk->getReactions()[0]->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->isDiscountActive(), 'idle');
                Assert::false($reaction->Used, 'pass');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'retarget and clear do nothing once the discount is idle' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->retargetDiscountedCard(50);
                $reaction->clearActiveDiscount();
                Assert::false($reaction->isDiscountActive(), 'still idle');
            },
        ];
    }
}
