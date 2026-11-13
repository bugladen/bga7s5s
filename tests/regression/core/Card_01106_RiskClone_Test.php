<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01103;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01106_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;

class Card_01106_RiskClone_Test extends TestCase
{
    public function name(): string
    {
        return '_01106_RiskClone';
    }

    public function tests(): array
    {
        return [
            // WHY: Discard is pay-time. Shell must stay in Owner discard (buildCity) so
            // multi-step stolen actions still receive CharacterTargeted. Cleanup waits
            // for ActionResolved (card text: After it resolves).
            'discard marks PendingCleanup and queues no hide/sink/locker yet' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01103(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $improvId = 50;
                $clone = $world->placeCard(new _01106_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->OwnerId = 2;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;
                $clone->ParentCardId = $improvId;

                $discarded = new EventCardDiscardedFromHand();
                $discarded->cardId = $clone->Id;
                $discarded->ownerId = 2;
                $world->fireOn($clone, $discarded);

                Assert::true($clone->PendingCleanup, 'pending');
                Assert::true($clone->IsUpdated, 'persist flag');
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'hub still to run');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'no hide yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class), 'no sink yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no locker yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolve yet');
            },

            'ActionResolved for this shell hides, sinks original, lockers Improvising' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01103(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $improv = $world->placeCard(new _01106(), 'Discard-1', 1);
                $clone = $world->placeCard(new _01106_RiskClone(), 'Discard-2', 1);
                $clone->OwnerId = 2;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;
                $clone->ParentCardId = $improv->Id;
                $clone->PendingCleanup = true;

                $stolenActionId = $clone->Id . '_Action_01103a';
                $world->game->globals->set(Game::CHOSEN_ACTION, $stolenActionId);

                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fireOn($clone, $resolved);

                Assert::false($clone->PendingCleanup, 'cleared');

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::same($clone->Id, $removed[0]->cardId, 'shell remove');
                Assert::same(2, $removed[0]->playerId, 'OwnerId pile');
                Assert::true($removed[0]->permanentlyHide, 'hide after resolve');

                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($original->Id, $sink->cardId, 'original sinks');
                Assert::same(2, $sink->playerId, 'to owner deck');

                Assert::same($improv->Id, $removed[1]->cardId, 'Improvising leaves discard');
                Assert::same($improv->Id, $world->theah->queuedOfType(EventCardSentToLocker::class)[0]->cardId, 'Improvising locker');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no second resolve');
            },

            'ActionResolved for a different chosen action does not clean up' => function () {
                $world = new TestWorld();
                $clone = $world->placeCard(new _01106_RiskClone(), 'Discard-2', 1);
                $clone->OwnerId = 2;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = 99;
                $clone->ParentCardId = 50;
                $clone->PendingCleanup = true;
                $world->game->globals->set(Game::CHOSEN_ACTION, '999_Action_01058');

                $resolved = new EventActionResolved();
                $resolved->playerId = 1;
                $world->fireOn($clone, $resolved);

                Assert::true($clone->PendingCleanup, 'still pending');
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // Simulate drain: discard hub leaves shell in Owner discard until resolve hide.
            'hub discard leaves shell in Owner discard until ActionResolved hide' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01103(), Game::LOCATION_PERMANENTLY_HIDDEN, 2);
                $improv = $world->placeCard(new _01106(), 'Discard-1', 1);
                $clone = $world->placeCard(new _01106_RiskClone(), Game::LOCATION_HAND, 1);
                $clone->OwnerId = 2;
                $clone->ControllerId = 1;
                $clone->ClonedCardId = $original->Id;
                $clone->ParentCardId = $improv->Id;

                $discarded = EventFactory::createCardDiscardedFromHandEvent(2, $clone->Id, 0, false, true);
                $world->fireOn($clone, $discarded);
                $world->applyHub($discarded);

                Assert::true($clone->PendingCleanup, 'pending after discard');
                Assert::same('Discard-2', $clone->Location, 'hub wrote owner discard');

                $world->game->globals->set(Game::CHOSEN_ACTION, $clone->Id . '_Action_01103a');
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
