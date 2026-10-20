<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;

class Card_04cd01_RiskClone_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd01_RiskClone';
    }

    public function tests(): array
    {
        return [
            'is a risk shell' => function () {
                $clone = new _04cd01_RiskClone();
                Assert::instanceOf(Risk::class, $clone, 'Risk');
                Assert::same(0, $clone->ClonedCardId, 'unset');
            },

            // WHY: Penya was already sunk as the city-action cost. Discarding the clone
            // hides the shell and sinks the original risk to the bottom of its faction deck,
            // then resolves the action.
            'discarding the clone sinks the original risk and resolves the action' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01028(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $clone = $world->placeCard(new _04cd01_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->ClonedCardId = $original->Id;

                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = $clone->Id;
                $discarded->ownerId = 1;
                $world->fireOn($clone, $discarded);

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0];
                Assert::same($clone->Id, $removed->cardId, 'shell leaves discard');
                Assert::same(1, $removed->playerId, 'owner');
                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'shell hidden');

                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($original->Id, $sink->cardId, 'original');
                Assert::same(2, $sink->playerId, 'original controller');
                Assert::false($sink->onTop, 'bottom');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'clone controller');
            },

            'another card leaving hand does nothing' => function () {
                $world = new TestWorld();
                $clone = $world->placeCard(new _04cd01_RiskClone(), Game::LOCATION_HAND, 1);
                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = 999;
                $world->fireOn($clone, $discarded);
                Assert::count(0, $world->theah->queuedEvents, 'silent');
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'stays');
            },
        ];
    }
}
