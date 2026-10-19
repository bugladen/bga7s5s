<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;

class Card_03039_Test extends TestCase
{
    public function name(): string
    {
        return '_03039 Íñigo Rocoso';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille duelist with a technique' => function () {
                $inigo = new _03039();
                Assert::instanceOf(Character::class, $inigo, 'character');
                Assert::same(4, $inigo->Resolve, 'resolve');
                Assert::same(3, $inigo->Combat, 'combat');
                Assert::same(1, $inigo->Finesse, 'finesse');
                Assert::same(2, $inigo->Influence, 'influence');
                Assert::true($inigo->hasFaction('Castille'), 'Castille');
                Assert::true($inigo->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($inigo->hasTrait('Duelist'), 'Duelist');
                Assert::true($inigo->hasTrait('Castille'), 'Castille trait');
                Assert::instanceOf(Technique_03039::class, $inigo->getTechniques()[0], 'technique');
            },

            // WHY: +1 Finesse only on the transition into "has a Weapon", and -1 only when
            // the last Weapon leaves. The Attachments list already reflects the new set.
            // Fate's Silence skips handleEvent, so blank/unblank restamp the bonus.
            'the first weapon raises Finesse, and the last weapon leaving lowers it' => function () {
                $world = new TestWorld();
                $inigo = $world->placeCharacter(new _03039(), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _03044(), Game::LOCATION_CITY_DOCKS, 1);
                $second = $world->placeCard(new _03044(), Game::LOCATION_CITY_DOCKS, 1);
                $attire = $world->placeCard(new _03043(), Game::LOCATION_CITY_DOCKS, 1);
                $inigo->Attachments = [$weapon->Id];

                $equipped = new EventAttachmentEquipped();
                $equipped->theah = $world->theah;
                $equipped->characterId = $inigo->Id;
                $equipped->attachmentId = $weapon->Id;
                $inigo->handleEvent($equipped);
                $bonus = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                Assert::same($inigo->ModifiedFinesse, $bonus->OldFinesse, 'from printed');
                Assert::same($inigo->ModifiedFinesse + 1, $bonus->NewFinesse, 'plus one');

                $inigo->Attachments[] = $second->Id;
                $equipped->attachmentId = $second->Id;
                $world->theah->takeQueuedEvents();
                $inigo->handleEvent($equipped);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'already armed');

                $inigo->Attachments = [$weapon->Id, $attire->Id];
                $unequipped = new EventAttachmentUnequipped();
                $unequipped->theah = $world->theah;
                $unequipped->characterId = $inigo->Id;
                $unequipped->attachmentId = $second->Id;
                $inigo->handleEvent($unequipped);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'a weapon remains');

                $inigo->Attachments = [$attire->Id];
                $unequipped->attachmentId = $weapon->Id;
                $inigo->handleEvent($unequipped);
                $drop = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                Assert::same($inigo->ModifiedFinesse - 1, $drop->NewFinesse, 'last weapon');

                $inigo->Attachments = [$attire->Id];
                $equipped->attachmentId = $attire->Id;
                $world->theah->takeQueuedEvents();
                $inigo->handleEvent($equipped);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'not a weapon');
            },

            'blanking removes the weapon bonus, and unblanking puts it back' => function () {
                $world = new TestWorld();
                $inigo = $world->placeCharacter(new _03039(), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _03044(), Game::LOCATION_CITY_DOCKS, 1);
                $inigo->Attachments = [$weapon->Id];
                $inigo->onAbilitiesBlanked($world->theah);
                Assert::same($inigo->ModifiedFinesse - 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'blanked');

                $world->theah->takeQueuedEvents();
                $inigo->ControllerId = 0;
                $inigo->onAbilitiesBlanked($world->theah);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'uncontrolled');
                $inigo->ControllerId = 1;

                $inigo->onAbilitiesUnblanked($world->theah);
                Assert::same($inigo->ModifiedFinesse + 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'unblanked');

                $world->theah->takeQueuedEvents();
                $inigo->IsDying = true;
                $inigo->onAbilitiesUnblanked($world->theah);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'dying');
                $inigo->IsDying = false;
                $world->game->discardOrLockerByCardId[$inigo->Id] = true;
                $inigo->onAbilitiesUnblanked($world->theah);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'locker');
            },
        ];
    }
}
