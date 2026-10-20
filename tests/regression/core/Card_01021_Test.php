<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;

class Card_01021_Test extends TestCase
{
    public function name(): string
    {
        return "_01021 Legion's Caress";
    }

    public function tests(): array
    {
        return [
            'constructs FactionAttachment with equip-to-opponents and combat values' => function () {
                $caress = new _01021();
                Assert::instanceOf(FactionAttachment::class, $caress, 'FactionAttachment');
                Assert::true($caress->CanEquipToOpponents, 'CanEquipToOpponents');
                Assert::same(1, $caress->WealthCost, 'WealthCost');
                Assert::true($caress->DashedRiposte, 'DashedRiposte');
                Assert::same(2, $caress->Parry, 'Parry');
                Assert::same(3, $caress->Thrust, 'Thrust');
                Assert::true($caress->hasTrait('Poison'), 'Poison');
                Assert::true($caress->hasTrait('Sabotage'), 'Sabotage');
                Assert::true($caress->hasTrait('Unique'), 'Unique');
                Assert::true($caress->hasFaction('Vodacce'), 'Faction');
            },

            'eventCheck rejects Leader and non-city targets' => function () {
                $world = new TestWorld();
                $caress = $world->placeCard(new _01021(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new _01006(), Game::LOCATION_CITY_DOCKS, 2);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);
                $city = $world->placeCharacter(new GenericCharacter('City'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $caress->Id;
                $event->theah = $world->theah;

                $event->characterId = $leader->Id;
                $threw = false;
                try {
                    $caress->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Leader rejected');

                $event->characterId = $home->Id;
                $threw = false;
                try {
                    $caress->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Home rejected');

                $event->characterId = $city->Id;
                $caress->eventCheck($event);
                Assert::true(true, 'city non-Leader ok');
            },

            // WHY regression: En Garde = EventCardEngarded (not EventCardEngaged/Engage)
            'wounds equipped character on EventCardEngarded' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
                $caress = $world->placeCard(new _01021(), Game::LOCATION_CITY_DOCKS, 1);
                $caress->AttachedToId = $host->Id;

                $event = new EventCardEngarded();
                $event->cardId = $host->Id;
                $world->fireOn($caress, $event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($host->Id, $wounds[0]->characterId, 'host wounded');
                Assert::same(1, $wounds[0]->wounds, '1 wound');
            },

            'does not wound on EventCardEngaged (Engage)' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
                $caress = $world->placeCard(new _01021(), Game::LOCATION_CITY_DOCKS, 1);
                $caress->AttachedToId = $host->Id;

                $event = new EventCardEngaged();
                $event->cardId = $host->Id;
                $world->fireOn($caress, $event);

                Assert::count(0, $world->theah->queuedEvents, 'Engage ignored');
            },
        ];
    }
}
