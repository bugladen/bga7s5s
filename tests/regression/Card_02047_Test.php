<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_02047_Test extends TestCase
{
    public function name(): string
    {
        return '_02047 Temnota';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Poluchatel attachment with a City Action' => function () {
                $temnota = new _02047();
                Assert::instanceOf(FactionAttachment::class, $temnota, 'attachment');
                Assert::instanceOf(IHasActions::class, $temnota, 'actions');
                Assert::same('Clever & Gifted', $temnota->Title, 'title');
                Assert::same(1, $temnota->ResolveModifier, 'Resolve');
                Assert::same(0, $temnota->CombatModifier, 'Combat');
                Assert::same(0, $temnota->FinesseModifier, 'Finesse');
                Assert::same(0, $temnota->InfluenceModifier, 'Influence');
                Assert::same(0, $temnota->WealthCost, 'Wealth');
                Assert::same(0, $temnota->Riposte, 'Riposte');
                Assert::same(2, $temnota->Parry, 'Parry');
                Assert::same(3, $temnota->Thrust, 'Thrust');
                Assert::true($temnota->hasFaction('Ussura'), 'Ussura');
                Assert::true($temnota->hasTrait('Poluchatel'), 'Poluchatel');
                Assert::true($temnota->hasTrait('Animal'), 'Animal');
                Assert::true($temnota->hasTrait('Raven'), 'Raven');
                Assert::true($temnota->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_02047::class, $temnota->getActions()[0], 'action');
            },

            'owns the action when placed' => function () {
                $world = new TestWorld();
                $temnota = $world->placeCard(new _02047(), Game::LOCATION_HAND, 1);
                Assert::same($temnota->Id, $temnota->getActions()[0]->OwnerId, 'owner');
            },

            'equipping grants Sorcerer and unequipping removes it' => function () {
                $world = new TestWorld();
                $temnota = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($host->hasTrait('Sorcerer'), 'before');

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $temnota->Id;
                $equipped->characterId = $host->Id;
                $world->fireOn($temnota, $equipped);
                Assert::true($host->hasTrait('Sorcerer'), 'gained');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->attachmentId = $temnota->Id;
                $unequipped->characterId = $host->Id;
                $world->fireOn($temnota, $unequipped);
                Assert::false($host->hasTrait('Sorcerer'), 'lost');
            },

            'another attachment equipping does not grant Sorcerer' => function () {
                $world = new TestWorld();
                $temnota = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = 999;
                $equipped->characterId = $host->Id;
                $world->fireOn($temnota, $equipped);

                Assert::false($host->hasTrait('Sorcerer'), 'untouched');
            },
        ];
    }
}
