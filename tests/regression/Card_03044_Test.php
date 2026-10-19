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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_03044_Test extends TestCase
{
    public function name(): string
    {
        return '_03044 Torres Cloak';
    }

    public function tests(): array
    {
        return [
            'constructs an off-hand cloak that only equips to a Duelist' => function () {
                $cloak = new _03044();
                Assert::instanceOf(FactionAttachment::class, $cloak, 'attachment');
                Assert::instanceOf(IHasReactions::class, $cloak, 'reactions');
                Assert::same(0, $cloak->WealthCost, 'wealth');
                Assert::true($cloak->OffHand, 'off-hand');
                Assert::same(1, $cloak->Riposte, 'riposte');
                Assert::same(1, $cloak->Parry, 'parry');
                Assert::same(1, $cloak->Thrust, 'thrust');
                Assert::true($cloak->hasFaction('Castille'), 'Castille');
                Assert::true($cloak->hasTrait('Weapon'), 'Weapon');
                Assert::true($cloak->hasTrait('Cloak'), 'Cloak');
                Assert::true($cloak->hasTrait('Torres'), 'Torres');
                Assert::instanceOf(Reaction_03044::class, $cloak->getReactions()[0], 'reaction');

                $duelist = new GenericCharacter('Duelist', ['Duelist']);
                $civilian = new GenericCharacter('Civilian', ['Hero']);
                Assert::true($cloak->canAttachTo($duelist), 'duelist');
                Assert::false($cloak->canAttachTo($civilian), 'not a duelist');
            },

            'equipping to a civilian is rejected' => function () {
                $world = new TestWorld();
                $cloak = $world->placeCard(new _03044(), Game::LOCATION_HAND, 1);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $equipping = new EventAttachmentEquipping();
                $equipping->theah = $world->theah;
                $equipping->attachmentId = $cloak->Id;
                $equipping->characterId = $civilian->Id;
                $threw = false;
                try {
                    $cloak->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not a duelist');

                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $equipping->characterId = $duelist->Id;
                $cloak->eventCheck($equipping);
                Assert::true(true, 'duelist is legal');
            },
        ];
    }
}
