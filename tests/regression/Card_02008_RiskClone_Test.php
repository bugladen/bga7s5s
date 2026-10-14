<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02008_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class Card_02008_RiskClone_Test extends TestCase
{
    public function name(): string
    {
        return '_02008_RiskClone';
    }

    /**
     * Face-down clone under a character; original Risk permanently hidden.
     *
     * @return array{0:_02008_RiskClone,1:_02007,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
        $original = $world->placeCard(new _02007(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
        /** @var _02008_RiskClone $clone */
        $clone = $world->placeCard(new _02008_RiskClone(), Game::LOCATION_CITY_DOCKS, 1);
        $clone->ClonedCardId = $original->Id;
        $clone->TargetCharacterId = $host->Id;
        $clone->AttachedToId = $host->Id;
        $host->Attachments[] = $clone->Id;
        return [$clone, $original, $host];
    }

    public function tests(): array
    {
        return [
            'constructs face-down FactionAttachment clone' => function () {
                $clone = new _02008_RiskClone();
                Assert::instanceOf(FactionAttachment::class, $clone, 'FactionAttachment');
                Assert::true($clone->FaceDown, 'face-down');
                Assert::same(0, $clone->ClonedCardId, 'default clone id');
                Assert::same(0, $clone->TargetCharacterId, 'default target');
            },

            // WHY (journal 2026-03-16-01): returns original risk to hand on EventCharacterDestroyed
            // for TargetCharacterId — NOT on discard-from-play alone.
            'character destroyed returns cloned Risk to hand' => function () {
                $world = new TestWorld();
                [$clone, $original, $host] = $this->scene($world);

                $event = new EventCharacterDestroyed();
                $event->characterId = $host->Id;
                $event->playerId = 2;
                $world->fireOn($clone, $event);

                $hands = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $hands, 'to hand');
                Assert::same($original->Id, $hands[0]->cardId, 'original');
                Assert::same(1, $hands[0]->playerId, 'controller');
            },

            'destroying a different character does not return the Risk' => function () {
                $world = new TestWorld();
                [$clone] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $other->Id;
                $world->fireOn($clone, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'no hand');
            },

            // WHY: discard-from-play only cleans the clone out of discard — does not restore Risk.
            'discard from play only hides the clone from discard pile' => function () {
                $world = new TestWorld();
                [$clone] = $this->scene($world);

                $event = new EventCardDiscardedFromPlay();
                $event->cardId = $clone->Id;
                $event->ownerId = 1;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($clone, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'no restore');
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                Assert::count(1, $removed, 'cleanup');
                Assert::same($clone->Id, $removed[0]->cardId, 'clone');
            },
        ];
    }
}
