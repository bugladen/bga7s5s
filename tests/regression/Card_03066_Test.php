<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03066;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_03066_Test extends TestCase
{
    public function name(): string
    {
        return '_03066 Shackles';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral restraint that can equip to opponents' => function () {
                $card = new _03066();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::true($card->CanEquipToOpponents, 'opponents');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(0, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Chains'), 'Chains');
                Assert::true($card->hasTrait('Restraint'), 'Restraint');
            },

            // WHY: The equip target is not "your performer". You need your own character at
            // that location with strictly higher Finesse. Same controller is rejected first.
            // Equipping stamps the cannot-move condition. End of High Drama destroys it.
            'equips only to an opposing character with less finesse' => function () {
                $world = new TestWorld();
                $shackles = $world->placeCard(new _03066(), Game::LOCATION_HAND, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $equipping = new EventAttachmentEquipping();
                $equipping->theah = $world->theah;
                $equipping->attachmentId = $shackles->Id;
                $equipping->characterId = $target->Id;
                $equipping->playerId = 1;

                $ally->ModifiedFinesse = 1;
                $target->ModifiedFinesse = 1;
                $threw = false;
                try {
                    $shackles->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not less finesse');

                $ally->ModifiedFinesse = 3;
                $shackles->eventCheck($equipping);

                $equipping->playerId = 2;
                $threw = false;
                try {
                    $shackles->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'same controller');

                Assert::false($shackles->canAttachTo($ally), 'ally controller matches shackles');
                Assert::true($shackles->canAttachTo($target), 'opposing');

                $shackles->AttachedToId = $target->Id;
                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $shackles->Id;
                $equipped->characterId = $target->Id;
                $world->fireOn($shackles, $equipped);
                Assert::true($target->hasCondition(Game::SHACKLES_CONDITION), 'stamped');

                $end = new EventHighDramaPhaseEnd();
                $world->fireOn($shackles, $end);
                Assert::same($shackles->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0];
                Assert::same($shackles->Id, $discard->cardId, 'discard');
                Assert::true($discard->asEffect, 'effect');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->attachmentId = $shackles->Id;
                $unequipped->characterId = $target->Id;
                $world->fireOn($shackles, $unequipped);
                Assert::false($target->hasCondition(Game::SHACKLES_CONDITION), 'cleared');

                $shackles->AttachedToId = 0;
                $world->theah->queuedEvents = [];
                $world->fireOn($shackles, $end);
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'not attached');
            },
        ];
    }
}
