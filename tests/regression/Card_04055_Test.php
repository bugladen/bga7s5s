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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04055;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_04055_Test extends TestCase
{
    public function name(): string
    {
        return '_04055 Sturdy Shield';
    }

    public function tests(): array
    {
        return [
            // WHY: Offhand, so it is not a second armor. Equip check is direct and uses
            // ModifiedCombat, not the printed number alone.
            'equips only to 2 or more combat' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04055(), Game::LOCATION_HAND, 1);
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::true($card->OffHand, 'offhand');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Armor'), 'Armor');
                Assert::true($card->hasTrait('Shield'), 'Shield');
                Assert::count(2, $card->getTechniques(), 'techniques');

                $weak = $world->placeCharacter(new GenericCharacter('Weak'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($card->canAttachTo($weak), 'combat 1');
                $equipping = new EventAttachmentEquipping();
                $equipping->attachmentId = $card->Id;
                $equipping->characterId = $weak->Id;
                $equipping->theah = $world->theah;
                $threw = false;
                try {
                    $card->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'equip check');

                $weak->ModifiedCombat = 2;
                Assert::true($card->canAttachTo($weak), 'enough combat');
                $card->eventCheck($equipping);
            },
        ];
    }
}
