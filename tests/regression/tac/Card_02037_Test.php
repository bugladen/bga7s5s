<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;

class Card_02037_Test extends TestCase
{
    public function name(): string
    {
        return '_02037 Mysta';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Cat attachment with dashed Riposte, Technique, and Reaction' => function () {
                $mysta = new _02037();
                Assert::instanceOf(FactionAttachment::class, $mysta, 'attachment');
                Assert::instanceOf(IHasTechniques::class, $mysta, 'techniques');
                Assert::instanceOf(IHasReactions::class, $mysta, 'reactions');
                Assert::same(0, $mysta->WealthCost, 'Wealth');
                Assert::same(0, $mysta->Riposte, 'Riposte');
                Assert::true($mysta->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $mysta->Parry, 'Parry');
                Assert::same(2, $mysta->Thrust, 'Thrust');
                Assert::true($mysta->hasFaction('Castille'), 'Castille');
                Assert::true($mysta->hasTrait('Animal'), 'Animal');
                Assert::true($mysta->hasTrait('Cat'), 'Cat');
                Assert::true($mysta->hasTrait('Menace'), 'Menace');
                Assert::true($mysta->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_02037::class, $mysta->getTechniques()[0], 'technique');
                Assert::instanceOf(Reaction_02037::class, $mysta->getReactions()[0], 'reaction');
            },

            'owns technique and reaction when placed' => function () {
                $world = new TestWorld();
                $mysta = $world->placeCard(new _02037(), Game::LOCATION_HAND, 1);
                Assert::same($mysta->Id, $mysta->getTechniques()[0]->OwnerId, 'technique');
                Assert::same($mysta->Id, $mysta->getReactions()[0]->OwnerId, 'reaction');
            },

            // WHY: Discard from hand is canceled and Mysta sinks (bottom of faction deck).
            'discard from hand is canceled and sinks Mysta' => function () {
                $world = new TestWorld();
                $mysta = $world->placeCard(new _02037(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $mysta->Id;
                $world->fireOn($mysta, $event);

                Assert::true($event->canceled, 'canceled');
                $sunk = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::count(1, $sunk, 'sink');
                Assert::same($mysta->Id, $sunk[0]->cardId, 'card');
                Assert::same(1, $sunk[0]->playerId, 'player');
                Assert::false($sunk[0]->onTop, 'bottom');
            },

            'discard from play is canceled and sinks Mysta' => function () {
                $world = new TestWorld();
                $mysta = $world->placeCard(new _02037(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCardDiscardedFromPlay();
                $event->cardId = $mysta->Id;
                $world->fireOn($mysta, $event);

                Assert::true($event->canceled, 'canceled');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'sink');
            },

            'another card discarded does not sink Mysta' => function () {
                $world = new TestWorld();
                $mysta = $world->placeCard(new _02037(), Game::LOCATION_HAND, 1);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = 999;
                $world->fireOn($mysta, $event);

                Assert::false($event->canceled, 'not canceled');
                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
