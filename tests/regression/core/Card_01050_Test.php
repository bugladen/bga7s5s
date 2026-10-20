<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;

class Card_01050_Test extends TestCase
{
    public function name(): string
    {
        return '_01050 Unsavory Salve';
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with Technique_01050' => function () {
                $salve = new _01050();
                Assert::instanceOf(FactionAttachment::class, $salve, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $salve, 'techniques');
                Assert::same(0, $salve->WealthCost, 'WealthCost');
                Assert::same(2, $salve->Parry, 'Parry');
                Assert::same(3, $salve->Thrust, 'Thrust');
                Assert::true($salve->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Technique_01050::class, $salve->getTechniques()[0], 'Technique_01050');
            },

            'eventCheck refuses equip without Weapon' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $salve = $world->placeCard(new _01050(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $salve->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $salve->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'needs weapon');
            },

            'eventCheck allows equip with Weapon' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $host->Attachments[] = $weapon->Id;
                $salve = $world->placeCard(new _01050(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $salve->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $salve->eventCheck($event);
                Assert::true(true, 'no throw');
            },

            'losing last Weapon discards Salve from play' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
                $salve = $world->placeCard(new _01050(), Game::LOCATION_CITY_DOCKS, 1);
                $salve->AttachedToId = $host->Id;
                // After weapon unequip: only salve remains (not a Weapon)
                $host->Attachments = [$salve->Id];

                $event = new EventAttachmentUnequipped();
                $event->characterId = $host->Id;
                $event->attachmentId = $weapon->Id;
                $world->fireOn($salve, $event);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip salve');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard');
            },
        ];
    }
}
