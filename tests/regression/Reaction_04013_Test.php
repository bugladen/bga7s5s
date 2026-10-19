<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04013_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04013';
    }

    public function tests(): array
    {
        return [
            // WHY: Unequip clears AttachedToId before the discard, so the host is stashed.
            // Artifacts and fakes are not salvageable. Cost 0 equips immediately.
            // Decline requeues the discard with Tomas in cancelDeclinedByCardIds, so a
            // second pass does not cancel it again.
            'salvages a non-artifact from his location and a decline lets the discard continue' => function () {
                $world = new TestWorld();
                $tomas = $world->placeCharacter(new _04013(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $garb = $world->placeCard(new _04006(), Game::LOCATION_CITY_DOCKS, 1);
                $garb->AttachedToId = $ally->Id;
                /** @var Reaction_04013 $reaction */
                $reaction = $tomas->getReactions()[0];

                $unequip = new EventAttachmentUnequipped();
                $unequip->attachmentId = $garb->Id;
                $unequip->characterId = $ally->Id;
                $unequip->theah = $world->theah;
                $reaction->handleEvent($unequip);

                $artifact = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 1);
                $artifact->addTrait($world->game, 'Artifact', true);
                $discardArtifact = new EventCardDiscardedFromPlay();
                $discardArtifact->cardId = $artifact->Id;
                $discardArtifact->fromLocation = Game::LOCATION_CITY_DOCKS;
                $discardArtifact->theah = $world->theah;
                $reaction->handleEvent($discardArtifact);
                Assert::false($discardArtifact->canceled, 'artifact is not salvaged');

                $discard = new EventCardDiscardedFromPlay();
                $discard->cardId = $garb->Id;
                $discard->fromLocation = Game::LOCATION_CITY_DOCKS;
                $discard->theah = $world->theah;
                $reaction->handleEvent($discard);
                Assert::true($discard->canceled, 'held');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'stacked offer');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'equip_' . $ally->Id);
                $equipped = $world->theah->queuedOfType(EventAttachmentEquipping::class)[0];
                Assert::same($ally->Id, $equipped->characterId, 'equipped instead');
                Assert::same($garb->Id, $equipped->attachmentId, 'the garb');
                Assert::true($reaction->Used, 'used');
                Assert::same('done', $world->game->gamestate->transitions[0], 'done');

                $reaction->Used = false;
                $garb->WealthCost = 1;
                $garb->AttachedToId = $ally->Id;
                $reaction->handleEvent($unequip);
                $paid = new EventCardDiscardedFromPlay();
                $paid->cardId = $garb->Id;
                $paid->fromLocation = Game::LOCATION_CITY_DOCKS;
                $paid->theah = $world->theah;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($paid);
                $hand = $world->placeCard(new _04006(), Game::LOCATION_HAND, 1);
                $reaction->performReaction($world->game, 0, '', 'equip_' . $ally->Id);
                Assert::same('pay', $reaction->getReactionDescription($world->theah) !== '' ? 'pay' : '', 'stage moved');
                $reaction->performReaction($world->game, 0, '', 'pay-' . $hand->Id);
                Assert::same($hand->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'paid');

                $reaction->Used = false;
                $garb->WealthCost = 0;
                $garb->AttachedToId = $ally->Id;
                $reaction->handleEvent($unequip);
                $again = new EventCardDiscardedFromPlay();
                $again->cardId = $garb->Id;
                $again->fromLocation = Game::LOCATION_CITY_DOCKS;
                $again->theah = $world->theah;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($again);
                $reaction->performReaction($world->game, 0, '', 'decline');
                $requeued = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0];
                Assert::true(in_array($tomas->Id, $requeued->cancelDeclinedByCardIds, true), 'declined');
                $requeued->theah = $world->theah;
                $requeued->canceled = false;
                $reaction->Used = false;
                $reaction->handleEvent($requeued);
                Assert::false($requeued->canceled, 'second pass is not this reaction');
            },
        ];
    }
}
