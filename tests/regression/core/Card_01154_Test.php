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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01154;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01154;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_01154_Test extends TestCase
{
    public function name(): string
    {
        return '_01154 Corpse Speak';
    }

    public function tests(): array
    {
        return [
            'constructs Sorcery Unguent FactionAttachment with Influence locked to 0' => function () {
                $speak = new _01154();
                Assert::instanceOf(FactionAttachment::class, $speak, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $speak, 'actions');
                Assert::same(0, $speak->WealthCost, 'wealth');
                Assert::true($speak->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $speak->Parry, 'Parry');
                Assert::same(3, $speak->Thrust, 'Thrust');
                Assert::true($speak->InfluenceLocked, 'Influence locked');
                Assert::same(0, $speak->InfluenceLockedValue, 'locked to 0');
                Assert::true($speak->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($speak->hasTrait('Unguent'), 'Unguent');
                Assert::instanceOf(Action_01154::class, $speak->getActions()[0], 'Action_01154');
            },

            'action id stamped once placed' => function () {
                $world = new TestWorld();
                $speak = $world->placeCard(new _01154(), Game::LOCATION_HAND, 1);
                Assert::same($speak->Id . '_Action_01154', $speak->getActions()[0]->Id, 'id');
            },

            'eventCheck allows equip to a Sorcerer' => function () {
                $world = new TestWorld();
                $speak = $world->placeCard(new _01154(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(
                    new GenericCharacter('Witch', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $speak->Id;
                $event->characterId = $sorcerer->Id;
                $event->theah = $world->theah;
                $speak->eventCheck($event);
                Assert::true(true, 'allowed');
            },

            'eventCheck rejects equip to a non-Sorcerer' => function () {
                $world = new TestWorld();
                $speak = $world->placeCard(new _01154(), Game::LOCATION_HAND, 1);
                $thug = $world->placeCharacter(new GenericCharacter('Thug'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $speak->Id;
                $event->characterId = $thug->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $speak->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
