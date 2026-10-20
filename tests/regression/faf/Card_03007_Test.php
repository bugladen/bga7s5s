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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03007;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_03007_Test extends TestCase
{
    public function name(): string
    {
        return "_03007 Matushka's Shears";
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce Strega weapon' => function () {
                $shears = new _03007();
                Assert::instanceOf(FactionAttachment::class, $shears, 'attachment');
                Assert::instanceOf(IHasReactions::class, $shears, 'reactions');
                Assert::same(1, $shears->WealthCost, 'Wealth');
                Assert::same(1, $shears->CombatModifier, 'Combat');
                Assert::same(3, $shears->Riposte, 'Riposte');
                Assert::same(0, $shears->Parry, 'Parry');
                Assert::true($shears->DashedParry, 'dashed Parry');
                Assert::same(0, $shears->Thrust, 'Thrust');
                Assert::true($shears->DashedThrust, 'dashed Thrust');
                Assert::true($shears->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($shears->hasTrait('Weapon'), 'Weapon');
                Assert::true($shears->hasTrait('Melee'), 'Melee');
                Assert::true($shears->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::true($shears->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Reaction_03007::class, $shears->getReactions()[0], 'reaction');
            },

            'equips only to a Strega' => function () {
                $world = new TestWorld();
                $shears = $world->placeCard(new _03007(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(new GenericCharacter('Strand', ['Strega']), Game::LOCATION_CITY_DOCKS, 1);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($shears->canAttachTo($strega), 'strega');
                Assert::false($shears->canAttachTo($civilian), 'not a strega');

                $event = new EventAttachmentEquipping();
                $event->theah = $world->theah;
                $event->attachmentId = $shears->Id;
                $event->characterId = $civilian->Id;
                $threw = false;
                try {
                    $shears->eventCheck($event);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'equip check');

                $event->characterId = $strega->Id;
                $shears->eventCheck($event);
                Assert::true(true, 'strega equip is allowed');

                $event->attachmentId = 0;
                $event->characterId = $civilian->Id;
                $shears->eventCheck($event);
                Assert::true(true, 'another attachment id is ignored');
            },
        ];
    }
}
