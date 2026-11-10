<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Timebomb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05TimebombFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_05Timebomb_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Timebomb';
    }

    /**
     * @return array{0:_05TimebombFlipped,1:GenericLeader,2:GenericCharacter}
     */
    private function equippedHeart(TestWorld $world): array
    {
        $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var _05TimebombFlipped $heart */
        $heart = $world->placeCard(new _05TimebombFlipped(), Game::LOCATION_CITY_DOCKS, 1);
        $heart->AttachedToId = $leader->Id;
        $leader->Attachments[] = $heart->Id;
        $world->theah->leadersByPlayerId[1] = $leader;
        return [$heart, $leader, $foe];
    }

    public function tests(): array
    {
        return [
            'is klim grabiezca antiquarian scion' => function () {
                $card = new _05Timebomb();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(3, $card->Resolve, 'resolve');
                Assert::same(1, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(0, $card->Influence, 'influence');
                Assert::true($card->IsDoubleSided, 'double-sided');
                Assert::true($card->hasTrait('Antiquarian'), 'Antiquarian');
                Assert::true($card->hasTrait('Scion'), 'Scion');
                Assert::true($card->hasTrait('Ussura'), 'Ussura');
                Assert::false($card->canEnterApproachDeck(), 'approach ban');
            },

            'porcelain heart is unique syrneth artifact attachment' => function () {
                $heart = new _05TimebombFlipped();
                Assert::instanceOf(FactionAttachment::class, $heart, 'FactionAttachment');
                Assert::same(1, $heart->ResolveModifier, '+1 Resolve');
                Assert::same(1, $heart->InfluenceModifier, '+1 Influence');
                Assert::same(0, $heart->WealthCost, 'WealthCost');
                Assert::true($heart->IsDoubleSided, 'double-sided');
                Assert::true($heart->hasTrait('Artifact'), 'Artifact');
                Assert::true($heart->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($heart->hasTrait('Unique'), 'Unique');
                Assert::false($heart->canBeMoved(), 'cannot be moved');
            },

            // WHY: Forced at end of HD only while at Home — city Klim is a no-op.
            // Wounds are 2 each to Klim then Leader (printed order).
            'end of high drama at home wounds klim and leader twice' => function () {
                $world = new TestWorld();
                $klim = $world->placeCharacter(new _05Timebomb(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;

                $end = new EventHighDramaPhaseEnd();
                $world->fireOn($klim, $end);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'two wound events');
                Assert::same($klim->Id, $wounds[0]->characterId, 'klim first');
                Assert::same(2, $wounds[0]->wounds, 'klim twice');
                Assert::same($leader->Id, $wounds[1]->characterId, 'leader second');
                Assert::same(2, $wounds[1]->wounds, 'leader twice');
            },

            'end of high drama in city does not wound' => function () {
                $world = new TestWorld();
                $klim = $world->placeCharacter(new _05Timebomb(), Game::LOCATION_CITY_DOCKS, 1);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;

                $world->fireOn($klim, new EventHighDramaPhaseEnd());
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wounds');
            },

            // WHY: Cancel Destroy + flip to Porcelain Heart, owner takes control, equip to Leader.
            // Same Id; card_type update; AttachmentEquipping queued (not locker).
            'destroy flips to porcelain heart and equips owner leader' => function () {
                $world = new TestWorld();
                $klim = $world->placeCharacter(new _05Timebomb(), Game::LOCATION_CITY_DOCKS, 1);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;

                $destroy = new EventCharacterDestroyed();
                $destroy->characterId = $klim->Id;
                $destroy->playerId = 1;
                $destroy->reason = 'test';
                $world->fireOn($klim, $destroy);

                Assert::true($destroy->canceled, 'destroy canceled');
                $heart = $world->theah->getCardById($klim->Id);
                Assert::instanceOf(_05TimebombFlipped::class, $heart, 'flipped');
                Assert::same(1, $heart->ControllerId, 'owner controls');
                Assert::same(1, $heart->OwnerId, 'owner');

                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'equip queued');
                Assert::same($leader->Id, $equip[0]->characterId, 'to leader');
                Assert::same($heart->Id, $equip[0]->attachmentId, 'heart');
                Assert::same(1, $equip[0]->playerId, 'owner equips');

                Assert::true(
                    (bool) array_filter(
                        $world->game->dbQueries,
                        fn($sql) => str_contains($sql, "card_type = '05TimebombFlipped'")
                    ),
                    'card_type updated'
                );
            },

            // WHY: Opponent-controlled Klim still returns the Heart to OwnerId's Leader.
            'destroy while controlled by opponent returns heart to owner' => function () {
                $world = new TestWorld();
                $klim = $world->placeCharacter(new _05Timebomb(), Game::LOCATION_CITY_DOCKS, 2);
                $klim->OwnerId = 1;
                $ownerLeader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->leadersByPlayerId[1] = $ownerLeader;

                $destroy = new EventCharacterDestroyed();
                $destroy->characterId = $klim->Id;
                $destroy->playerId = 2;
                $destroy->reason = 'test';
                $world->fireOn($klim, $destroy);

                Assert::true($destroy->canceled, 'canceled');
                $heart = $world->theah->getCardById($klim->Id);
                Assert::same(1, $heart->ControllerId, 'owner takes control');
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class)[0];
                Assert::same($ownerLeader->Id, $equip->characterId, 'owner leader');
                Assert::same(1, $equip->playerId, 'owner player');
            },

            // WHY: Syrneth Hand shape — stuck unless host IsDying (Leader death softlock otherwise).
            'eventCheck refuses unequip while the host is not dying' => function () {
                $world = new TestWorld();
                [$heart, $leader] = $this->equippedHeart($world);

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $heart->Id;
                $event->characterId = $leader->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $heart->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'stuck on host');
            },

            'eventCheck allows unequip when the host is dying' => function () {
                $world = new TestWorld();
                [$heart, $leader] = $this->equippedHeart($world);
                $leader->IsDying = true;

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $heart->Id;
                $event->characterId = $leader->Id;
                $event->theah = $world->theah;
                $heart->eventCheck($event);
                Assert::true(true, 'dying host may unequip');
            },

            'eventCheck refuses send to locker' => function () {
                $world = new TestWorld();
                [$heart] = $this->equippedHeart($world);

                $event = new EventCardSentToLocker();
                $event->cardId = $heart->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $heart->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'locker veto');
            },

            // WHY: Forced — adversary of equipped Leader announces → -1 Parry on that card.
            'adversary combat card loses 1 Parry while heart is equipped' => function () {
                $world = new TestWorld();
                [$heart, $leader, $foe] = $this->equippedHeart($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_ID, 1);
                $world->game->globals->set(Game::DUEL_ROUND, 1);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->adversaryId = $leader->Id;
                $event->addParry(3);
                $world->fireOn($heart, $event);

                Assert::same(2, $event->parry, '3 - 1');
                Assert::count(1, $event->explanations, 'explained');
            },

            'equipped character combat card is not reduced by heart' => function () {
                $world = new TestWorld();
                [$heart, $leader, $foe] = $this->equippedHeart($world);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $leader->Id;
                $event->adversaryId = $foe->Id;
                $event->addParry(3);
                $world->fireOn($heart, $event);

                Assert::same(3, $event->parry, 'unchanged');
                Assert::count(0, $event->explanations, 'no explanation');
            },
        ];
    }
}

