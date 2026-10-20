<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01139;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class Card_01139_Test extends TestCase
{
    public function name(): string
    {
        return '_01139 Strength of Ten';
    }

    public function tests(): array
    {
        return [
            'constructs Unique Ussura Risk with Action and Maneuver' => function () {
                $card = new _01139();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(1, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::false($card->goToLocker, 'flag off');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::instanceOf(Action_01139::class, $card->getActions()[0], 'Action_01139');
                Assert::instanceOf(Maneuver_01139::class, $card->getManeuvers()[0], 'Maneuver_01139');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_HAND, 1);
                Assert::same($card->Id . '_Action_01139', $card->getActions()[0]->Id, 'action id');
                Assert::same($card->Id . '_Maneuver_01139', $card->getManeuvers()[0]->Id, 'maneuver id');
            },

            // WHY (prod): Action path — AsPlayed discard + goToLocker redirects discard → locker.
            'AsPlayed discard with goToLocker removes from discard and sends to locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_HAND, 1);
                $card->goToLocker = true;

                $discard = new EventCardDiscardedFromHand();
                $discard->cardId = $card->Id;
                $discard->ownerId = 1;
                $discard->AsPlayed = true;
                $world->fireOn($card, $discard);

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'leave discard');
                $lockers = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $lockers, 'locker');
                Assert::same($card->Id, $lockers[0]->cardId, 'card');
                Assert::false($card->goToLocker, 'flag cleared');
            },

            'AsPlayed discard without goToLocker does nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_HAND, 1);

                $discard = new EventCardDiscardedFromHand();
                $discard->cardId = $card->Id;
                $discard->ownerId = 1;
                $discard->AsPlayed = true;
                $world->fireOn($card, $discard);

                Assert::count(0, $world->theah->queuedEvents, 'no locker path');
            },

            // WHY (prod comment): Maneuver sets goToLocker on calc; EndOfRound (not stats) drains it.
            'DuelEndOfRound with goToLocker sends to locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_DUELING_LINE, 1);
                $card->goToLocker = true;

                $world->fireOn($card, new EventDuelEndOfRound());

                $lockers = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $lockers, 'locker');
                Assert::same($card->Id, $lockers[0]->cardId, 'card');
                Assert::false($card->goToLocker, 'cleared');
            },

            'DuelEnd with goToLocker sends to locker as a backup' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_DUELING_LINE, 1);
                $card->goToLocker = true;

                $world->fireOn($card, new EventDuelEnd());

                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'locker');
                Assert::false($card->goToLocker, 'cleared');
            },

            'DuelEndOfRound without goToLocker does nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01139(), Game::LOCATION_DUELING_LINE, 1);

                $world->fireOn($card, new EventDuelEndOfRound());

                Assert::count(0, $world->theah->queuedEvents, 'no flag');
            },
        ];
    }
}
