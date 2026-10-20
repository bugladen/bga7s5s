<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01111;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01111;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;

class Card_01111_Test extends TestCase
{
    public function name(): string
    {
        return '_01111 Research';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with Action_01111 and dashed Riposte' => function () {
                $card = new _01111();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $card->Parry, 'Parry');
                Assert::same(3, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Discovery'), 'Discovery');
                Assert::true($card->hasTrait('Scholarship'), 'Scholarship');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(Action_01111::class, $card->getActions()[0], 'Action_01111');
            },

            // WHY: printed "Send this card to The Locker" — when discarded as played, the card
            // itself removes from discard and sends to locker (action resolve does not).
            'discarded as played removes from discard and sends to The Locker' => function () {
                $world = new TestWorld();
                $research = $world->placeCard(new _01111(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $research->Id;
                $event->ownerId = 1;
                $event->AsPlayed = true;
                $event->theah = $world->theah;
                $research->handleEvent($event);

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::count(1, $removed, 'removed from discard');
                Assert::same($research->Id, $removed[0]->cardId, 'this card');
                $locker = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $locker, 'to locker');
                Assert::same($research->Id, $locker[0]->cardId, 'this card');
            },

            'discarded not as played leaves Research in discard flow' => function () {
                $world = new TestWorld();
                $research = $world->placeCard(new _01111(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $research->Id;
                $event->ownerId = 1;
                $event->AsPlayed = false;
                $event->theah = $world->theah;
                $research->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no locker');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'no remove');
            },
        ];
    }
}
