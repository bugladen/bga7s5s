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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_04053_Test extends TestCase
{
    public function name(): string
    {
        return '_04053 Leather Spaulders';
    }

    public function tests(): array
    {
        return [
            // WHY: Equip check is direct. Printed Finesse is not enough when ModifiedFinesse
            // is below 2. eventCheck throws; canAttachTo agrees.
            'equips only to 2 or more finesse' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04053(), Game::LOCATION_HAND, 1);
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->ResolveModifier, 'resolve');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Armor'), 'Armor');
                Assert::count(1, $card->getReactions(), 'reaction');

                $slow = $world->placeCharacter(new GenericCharacter('Slow'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same(1, $slow->ModifiedFinesse, 'printed 1');
                Assert::false($card->canAttachTo($slow), 'too slow');
                $equipping = new EventAttachmentEquipping();
                $equipping->attachmentId = $card->Id;
                $equipping->characterId = $slow->Id;
                $equipping->theah = $world->theah;
                $threw = false;
                try {
                    $card->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'equip check');

                $slow->ModifiedFinesse = 2;
                Assert::true($card->canAttachTo($slow), 'enough finesse');
                $card->eventCheck($equipping);
            },
        ];
    }
}
