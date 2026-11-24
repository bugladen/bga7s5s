<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
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

            // WHY: Discard is pay-time. Shell must stay in Owner discard (buildCity) so
            // multi-step stolen actions (Move Along) still receive actFromCardWithId /
            // getInPlayActionById. Cleanup waits for ActionResolved (card text: After it resolves).
            'discard marks PendingCleanup and queues no hide/sink yet' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01028(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $clone = $world->placeCard(new _04cd01_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->OwnerId = 1;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;

                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = $clone->Id;
                $discarded->ownerId = 1;
                $world->fireOn($clone, $discarded);

                Assert::true($clone->PendingCleanup, 'pending');
                Assert::true($clone->IsUpdated, 'persist flag');
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'hub still to run');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'no hide yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'no sink yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolve yet');
            },

            'ActionResolved for this shell hides and sinks the original risk' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01028(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $clone = $world->placeCard(new _04cd01_RiskClone(), 'Discard-1', 1);
                $clone->OwnerId = 1;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;
                $clone->PendingCleanup = true;

                $stolenActionId = $clone->Id . '_Action_01028';
                $world->game->globals->set(Game::CHOSEN_ACTION, $stolenActionId);

                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fireOn($clone, $resolved);

                Assert::false($clone->PendingCleanup, 'cleared');

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0];
                Assert::same($clone->Id, $removed->cardId, 'shell leaves discard');
                Assert::same(1, $removed->playerId, 'OwnerId pile');
                Assert::true($removed->permanentlyHide, 'hub will hide after discard move');

                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($original->Id, $sink->cardId, 'original');
                Assert::same(2, $sink->playerId, 'original owner deck');
                Assert::false($sink->onTop, 'bottom');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no second resolve');
            },

            'ActionResolved for a different chosen action does not clean up' => function () {
                $world = new TestWorld();
                $clone = $world->placeCard(new _04cd01_RiskClone(), 'Discard-1', 1);
                $clone->OwnerId = 1;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = 99;
                $clone->PendingCleanup = true;
                $world->game->globals->set(Game::CHOSEN_ACTION, '999_Action_01056');

                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fireOn($clone, $resolved);

                Assert::true($clone->PendingCleanup, 'still pending');
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'another card leaving hand does nothing' => function () {
                $world = new TestWorld();
                $clone = $world->placeCard(new _04cd01_RiskClone(), Game::LOCATION_HAND, 1);
                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = 999;
                $world->fireOn($clone, $discarded);
                Assert::count(0, $world->theah->queuedEvents, 'silent');
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'stays');
                Assert::false($clone->PendingCleanup, 'not pending');
            },

            // Simulate drain: discard hub leaves shell in Owner discard until resolve hide.
            'hub discard leaves shell in Owner discard until ActionResolved hide' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01028(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $clone = $world->placeCard(new _04cd01_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->OwnerId = 1;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;

                $discarded = EventFactory::createCardDiscardedFromHandEvent(1, $clone->Id, 0, false, true);
                $world->fireOn($clone, $discarded);
                $world->applyHub($discarded);

                Assert::true($clone->PendingCleanup, 'pending after discard');
                Assert::same('Discard-1', $clone->Location, 'hub wrote owner discard');

                $world->game->globals->set(Game::CHOSEN_ACTION, $clone->Id . '_Action_01028');
                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fireOn($clone, $resolved);

                $hide = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0];
                Assert::true($hide->permanentlyHide, 'hide queued');
                $world->applyHub($hide);

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'shell gone for good');
            },
        ];
    }
}
