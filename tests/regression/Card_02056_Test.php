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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_02056_Test extends TestCase
{
    public function name(): string
    {
        return '_02056 Main Gauche';
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Offhand dagger with dashed Thrust' => function () {
                $card = new _02056();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasTechniques::class, $card, 'techniques');
                Assert::true($card->OffHand, 'Offhand');
                Assert::same(1, $card->WealthCost, 'Wealth');
                Assert::same(2, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->DashedThrust, 'dashed Thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Weapon'), 'Weapon');
                Assert::true($card->hasTrait('Melee'), 'Melee');
                Assert::true($card->hasTrait('Dagger'), 'Dagger');
                Assert::instanceOf(Technique_02056::class, $card->getTechniques()[0], 'technique');
            },

            'equips only to a Duelist' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _02056(), Game::LOCATION_HAND, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $brute = $world->placeCharacter(new GenericCharacter('Brute', ['Brute']), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($card->canAttachTo($duelist), 'duelist');
                Assert::false($card->canAttachTo($brute), 'not a duelist');

                $equipping = new EventAttachmentEquipping();
                $equipping->attachmentId = $card->Id;
                $equipping->characterId = $brute->Id;
                $equipping->theah = $world->theah;
                $threw = false;
                try {
                    $card->eventCheck($equipping);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'equip blocked');

                $equipping->characterId = $duelist->Id;
                $card->eventCheck($equipping);

                $equipping->attachmentId = 999;
                $equipping->characterId = $brute->Id;
                $card->eventCheck($equipping);
            },
        ];
    }
}
