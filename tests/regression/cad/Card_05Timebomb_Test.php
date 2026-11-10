<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Timebomb;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05TimebombFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_05Timebomb_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Timebomb';
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
        ];
    }
}
