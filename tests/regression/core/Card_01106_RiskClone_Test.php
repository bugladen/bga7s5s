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
            // WHY: Live report — Improvised Adaptable shell stuck in owner's discard,
            // reshuffled as a second Unique-looking card with thief ControllerId and no Maneuver.
            'discard queues permanentlyHide remove (hub-after-cards safe)' => function () {
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

                Assert::same(Game::LOCATION_HAND, $clone->Location, 'not hidden yet — hub still to run');

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::same($clone->Id, $removed[0]->cardId, 'shell remove');
                Assert::same(2, $removed[0]->playerId, 'OwnerId pile');
                Assert::true($removed[0]->permanentlyHide, 'hide after hub discard move');

                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($original->Id, $sink->cardId, 'original sinks');
                Assert::same(2, $sink->playerId, 'to owner deck');

                Assert::same($improvId, $removed[1]->cardId, 'Improvising leaves discard');
                Assert::same($improvId, $world->theah->queuedOfType(EventCardSentToLocker::class)[0]->cardId, 'Improvising locker');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'thief resolves');
            },

            // Simulate the real drain order: card handler → discard hub → permanentlyHide remove hub.
            'hub discard then permanentlyHide leave the shell hidden, not in discard' => function () {
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

                Assert::same('Discard-2', $clone->Location, 'hub wrote owner discard');

                $hide = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(2, $clone->Id, false, true);
                $world->applyHub($hide);

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'shell gone for good');
            },
        ];
    }
}
