<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;

class Card_01040_Test extends TestCase
{
    public function name(): string
    {
        return '_01040 Rena Klingenhalter';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Reaction_01040' => function () {
                $rena = new _01040();
                Assert::instanceOf(IHasReactions::class, $rena, 'reactions');
                Assert::same(5, $rena->Resolve, 'Resolve');
                Assert::same(2, $rena->Combat, 'Combat');
                Assert::same(3, $rena->Finesse, 'Finesse');
                Assert::same(1, $rena->Influence, 'Influence');
                Assert::true($rena->hasTrait('Weapons Master'), 'Weapons Master');
                Assert::true($rena->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Reaction_01040::class, $rena->getReactions()[0], 'Reaction_01040');
            },

            // WHY: +1 Combat only on first Weapon; second does not stack
            'first Weapon equip queues +1 Combat' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Attachments[] = $weapon->Id;

                $event = new EventAttachmentEquipped();
                $event->characterId = $rena->Id;
                $event->attachmentId = $weapon->Id;
                $world->fireOn($rena, $event);

                $mods = $world->theah->queuedOfType(EventCharacterCombatModified::class);
                Assert::count(1, $mods, 'combat mod');
                Assert::same($rena->ModifiedCombat + 1, $mods[0]->NewCombat, '+1');
            },

            'second Weapon equip does not stack combat' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);
                $w1 = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $w2 = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Attachments = [$w1->Id, $w2->Id];

                $event = new EventAttachmentEquipped();
                $event->characterId = $rena->Id;
                $event->attachmentId = $w2->Id;
                $world->fireOn($rena, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'no stack');
            },

            'last Weapon unequip queues -1 Combat' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                // After unequip, Attachments no longer includes the weapon
                $rena->Attachments = [];

                $event = new EventAttachmentUnequipped();
                $event->characterId = $rena->Id;
                $event->attachmentId = $weapon->Id;
                $world->fireOn($rena, $event);

                $mods = $world->theah->queuedOfType(EventCharacterCombatModified::class);
                Assert::count(1, $mods, 'combat mod');
                Assert::same($rena->ModifiedCombat - 1, $mods[0]->NewCombat, '-1');
            },

            'blanked removes weapon combat when armed' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Attachments[] = $weapon->Id;
                $rena->onAbilitiesBlanked($world->theah);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'blank removes');
            },
        ];
    }
}
