<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01102_Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class Card_01102_Test extends TestCase
{
    public function name(): string
    {
        return '_01102 Unfortunate';
    }

    public function tests(): array
    {
        return [
            'is a Castille Hubris Risk with Riposte 1 / Parry 0 / Thrust 3' => function () {
                $risk = new _01102();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::true($risk->hasFaction('Castille'), 'Castille');
                Assert::true($risk->hasTrait('Hubris'), 'Hubris');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'Riposte');
                Assert::same(0, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
            },

            // WHY: Forced — when YOUR round ends and this Risk is in the dueling line,
            // equip 01102_Attachment to the adversary. FakeGame records createRiskAttachment.
            'Forced EndOfRound equips the attachment to the duel adversary' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01102(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                // WHY: TestTheah::getDuelOpponentId always returns duelOpponent — set foe for challenger round.
                $world->theah->duelOpponent = $foe;
                $world->theah->duelActor = $actor;

                $event = new EventDuelEndOfRound();
                $event->playerId = 1;
                $event->actorId = $actor->Id;
                $event->theah = $world->theah;
                $risk->handleEvent($event);

                Assert::count(1, $world->game->createdRiskAttachments, 'createRiskAttachment');
                $created = $world->game->createdRiskAttachments[0];
                Assert::same('01102_Attachment', $created['className'], 'attachment class');
                Assert::same($risk->Id, $created['originalCardId'], 'original risk');
                Assert::same($foe->Location, $created['location'], 'at adversary location');
                Assert::same(1, $created['ownerId'], 'risk owner');
                Assert::same(2, $created['controllerId'], 'adversary controller');
                Assert::same($foe->Id, $created['targetId'], 'equip target');
            },

            'Forced does not fire when the Risk is not in the dueling line' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01102(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $foe;

                $event = new EventDuelEndOfRound();
                $event->playerId = 1;
                $event->actorId = $actor->Id;
                $event->theah = $world->theah;
                $risk->handleEvent($event);

                Assert::count(0, $world->game->createdRiskAttachments, 'not in line');
            },

            'Forced does not fire on the opponent\'s round' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01102(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $actor;

                $event = new EventDuelEndOfRound();
                $event->playerId = 2;
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $risk->handleEvent($event);

                Assert::count(0, $world->game->createdRiskAttachments, 'not controller\'s round');
            },

            // --- _01102_Attachment ---
            // WHY: FactionAttachment + copied RPT — Silence-style clone tooltip, and
            // stApplyCombatCardStats must not undefined-property if a clone leaks into a deck.
            'attachment is a FakeAttachment Hubris with -1 Finesse, RPT, and Action_01102' => function () {
                $att = new _01102_Attachment();
                Assert::instanceOf(Attachment::class, $att, 'Attachment');
                Assert::instanceOf(FactionAttachment::class, $att, 'FactionAttachment');
                Assert::instanceOf(IRiskAttachment::class, $att, 'IRiskAttachment');
                Assert::true($att->FakeAttachment, 'FakeAttachment');
                Assert::same(-1, $att->FinesseModifier, 'Finesse');
                Assert::same(1, $att->Riposte, 'Riposte');
                Assert::same(0, $att->Parry, 'Parry');
                Assert::same(3, $att->Thrust, 'Thrust');
                Assert::true($att->hasTrait('Hubris'), 'Hubris');
                Assert::count(1, $att->getActions(), 'one action');
                Assert::instanceOf(Action_01102::class, $att->getActions()[0], 'Action_01102');
            },

            // WHY: Host death used to plain-discard the FakeAttachment into the owner's
            // discard → faction deck. Must call removeRiskAttachment instead.
            'host unEquipAllAttachments restores via removeRiskAttachment' => function () {
                $world = new TestWorld();
                $original = $world->placeCard(new _01102(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
                $att = $world->placeCard(new _01102_Attachment(), Game::LOCATION_CITY_DOCKS, 2);
                $att->OwnerId = 1;
                $att->AttachedToId = $host->Id;
                $att->setOriginalCardId($original->Id);
                $host->Attachments[] = $att->Id;

                $host->unEquipAllAttachments($world->theah);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard clone');
                Assert::count(1, $world->theah->queuedOfType(EventCardHidden::class), 'hide clone');
                Assert::same(
                    $world->game->getPlayerDiscardDeckName(1),
                    $original->Location,
                    'original Risk back in owner discard'
                );
            },
        ];
    }
}
