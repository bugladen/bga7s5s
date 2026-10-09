<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01116;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IPayTimeCostDiscount;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01116b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01116b';
    }

    /** @return array{0:_01116,1:Reaction_01116b} */
    private function scene(TestWorld $world): array
    {
        $yevgeni = $world->placeCharacter(new _01116(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01116b $reaction */
        $reaction = $yevgeni->getReactions()[1];
        return [$yevgeni, $reaction];
    }

    private function enteringPay(TestWorld $world, int $playerId, int $cardId, int $payStateType = Game::PAY_STATE_EQUIP_ATTACHMENT): EventEnteringPayState
    {
        $event = new EventEnteringPayState();
        $event->playerId = $playerId;
        $event->cardId = $cardId;
        $event->payStateType = $payStateType;
        $event->internalId = '';
        $event->theah = $world->theah;
        return $event;
    }

    private function activateDiscount(TestWorld $world, Reaction_01116b $reaction, int $cardId, int $performerId = 0): void
    {
        $reaction->handleEvent($this->enteringPay($world, 1, $cardId));
        $world->theah->takeQueuedEvents();
        // WHY: calculateInHandPayDiscount(EQUIP) needs CHOSEN_PERFORMER for getEquipDiscount.
        if ($performerId > 0) {
            $world->game->globals->set(Game::CHOSEN_PERFORMER, $performerId);
        }
        $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
        $world->theah->takeQueuedEvents();
    }

    public function tests(): array
    {
        return [
            'implements IPayTimeCostDiscount' => function () {
                Assert::instanceOf(IPayTimeCostDiscount::class, new Reaction_01116b(), 'discount');
            },

            'buttons offer Activate and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['activate', 'pass'], $ids, 'buttons');
            },

            'offers (stacked) when paying for a non-Character Wealth card' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $filler = new EventPlayerTurnEnd();
                $world->theah->queueEvent($filler);

                $reaction->handleEvent($this->enteringPay($world, 1, $attachment->Id));

                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[0], 'stacked in front');
                Assert::same($reaction->Id, $world->theah->queuedEvents[0]->internalId, 'this reaction');
                Assert::same($yevgeni->Id, $world->theah->queuedEvents[0]->sourceId, 'source');
                Assert::same($filler, $world->theah->queuedEvents[1], 'filler behind');
            },

            'does not offer for a Character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $char = $world->placeCharacter(new GenericCharacter('Recruit'), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->enteringPay($world, 1, $char->Id, Game::PAY_STATE_RECRUIT_MERCENARY));

                Assert::count(0, $world->theah->queuedEvents, 'characters excluded');
            },

            // WHY: WealthCost 0 cards never enter a meaningful pay UI for this discount.
            'does not offer for a zero-cost Wealth card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $free = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1); // WealthCost 0

                $reaction->handleEvent($this->enteringPay($world, 1, $free->Id, Game::PAY_STATE_IN_HAND_ACTION));

                Assert::count(0, $world->theah->queuedEvents, 'zero cost');
            },

            'does not offer for an opponent\'s payment' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->enteringPay($world, 2, $attachment->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not our pay');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $reaction->Used = true;

                $reaction->handleEvent($this->enteringPay($world, 1, $attachment->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'activate marks Used, sets ABNORMAL_FLOW, and discounts that attachment' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);

                $this->activateDiscount($world, $reaction, $attachment->Id, $yevgeni->Id);

                Assert::true($reaction->Used, 'used');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW) === true, 'abnormal flow');
                Assert::true($reaction->isDiscountActive(), 'active');

                $explanations = [];
                Assert::same(1, $reaction->getEquipDiscount($world->theah, $yevgeni, $attachment, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'discount does not apply to a different attachment' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $chosen = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $other = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $this->activateDiscount($world, $reaction, $chosen->Id, $yevgeni->Id);

                $explanations = [];
                Assert::same(0, $reaction->getEquipDiscount($world->theah, $yevgeni, $other, $explanations), 'other');
            },

            'pass leaves the discount inactive' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $reaction->handleEvent($this->enteringPay($world, 1, $attachment->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::false($reaction->isDiscountActive(), 'inactive');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'turn end clears an active discount' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $this->activateDiscount($world, $reaction, $attachment->Id, $yevgeni->Id);

                $event = new EventPlayerTurnEnd();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->isDiscountActive(), 'cleared');
            },

            'ActionResolved clears an active discount' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $this->activateDiscount($world, $reaction, $attachment->Id, $yevgeni->Id);

                $event = new EventActionResolved();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->isDiscountActive(), 'cleared');
            },

            'DuelEndOfRound clears an active discount' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $attachment = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);
                $this->activateDiscount($world, $reaction, $attachment->Id, $yevgeni->Id);

                $event = new EventDuelEndOfRound();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->isDiscountActive(), 'cleared');
            },

            'Dusk resets Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::false($reaction->Used, 'reset');
            },
        ];
    }
}
