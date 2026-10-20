<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01170;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01170;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;

class Card_01170_Test extends TestCase
{
    public function name(): string
    {
        return '_01170 Opulence';
    }

    public function tests(): array
    {
        return [
            'constructs Wealth Fortune Risk with Action_01170' => function () {
                $risk = new _01170();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'WealthCost');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(1, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Wealth'), 'Wealth');
                Assert::true($risk->hasTrait('Fortune'), 'Fortune');
                // WHY: multi-faction shared Risk — no initializeFaction; Card defaults Neutral.
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::instanceOf(Action_01170::class, $risk->getActions()[0], 'Action_01170');
            },

            'ability id is stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01170(), Game::LOCATION_HAND, 1);
                Assert::same($risk->Id . '_Action_01170', $risk->getActions()[0]->Id, 'action id');
            },

            // WHY (journal 2026-03-31-10): Wealth "Send to Locker after paying costs" — only AsPayment.
            'discarded as payment sends to The Locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01170(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $card->Id;
                $event->ownerId = 1;
                $event->AsPayment = true;
                $world->fireOn($card, $event);

                $lockers = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $lockers, 'locker');
                Assert::same($card->Id, $lockers[0]->cardId, 'this card');
                Assert::same(1, $lockers[0]->playerId, 'owner');
            },

            'discarded as played does not send to locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01170(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $card->Id;
                $event->ownerId = 1;
                $event->AsPlayed = true;
                $event->AsPayment = false;
                $world->fireOn($card, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no locker');
            },

            'discarded neither as payment nor played does not send to locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01170(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $card->Id;
                $event->ownerId = 1;
                $world->fireOn($card, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no locker');
            },

            'payment discard of a different card id does nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01170(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $card->Id + 999;
                $event->ownerId = 1;
                $event->AsPayment = true;
                $world->fireOn($card, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'ignored');
            },
        ];
    }
}
