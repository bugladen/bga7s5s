<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02016;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_02016_Test extends TestCase
{
    public function name(): string
    {
        return '_02016 Cross of the Martyrs';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen FactionAttachment with Reaction_02016' => function () {
                $cross = new _02016();
                Assert::instanceOf(FactionAttachment::class, $cross, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $cross, 'reactions');
                Assert::same(1, $cross->WealthCost, 'Wealth');
                Assert::same(1, $cross->ResolveModifier, 'Resolve');
                Assert::same(0, $cross->Riposte, 'Riposte');
                Assert::same(2, $cross->Parry, 'Parry');
                Assert::same(2, $cross->Thrust, 'Thrust');
                Assert::true($cross->hasFaction('Eisen'), 'Eisen');
                Assert::true($cross->hasTrait('Holy'), 'Holy');
                Assert::true($cross->hasTrait('Relic'), 'Relic');
                Assert::true($cross->hasTrait('Battle Standard'), 'Battle Standard');
                Assert::true($cross->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Reaction_02016::class, $cross->getReactions()[0], 'Reaction');
            },

            'owns Reaction_02016 when placed' => function () {
                $world = new TestWorld();
                $cross = $world->placeCard(new _02016(), Game::LOCATION_HAND, 1);
                Assert::count(1, $cross->getReactions(), 'one');
                Assert::same($cross->Id, $cross->getReactions()[0]->OwnerId, 'owner');
            },

            // WHY: printed "Cannot equip to Sorcerers."
            'eventCheck refuses equip to Sorcerer' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(
                    new GenericCharacter('Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $cross = $world->placeCard(new _02016(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $cross->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $cross->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Sorcerer blocked');
            },

            'eventCheck allows equip to non-Sorcerer' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $cross = $world->placeCard(new _02016(), Game::LOCATION_HAND, 1);

                $event = new EventAttachmentEquipping();
                $event->attachmentId = $cross->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $cross->eventCheck($event);
                Assert::true(true, 'no throw');
            },

            // WHY: Equipped character gains Zealot; unequip removes it.
            'EventAttachmentEquipped adds Zealot trait' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $cross = $world->placeCard(new _02016(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $cross->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $cross->handleEvent($event);

                Assert::true($host->hasTrait('Zealot'), 'Zealot');
            },

            'EventAttachmentUnequipped removes Zealot trait' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $host->addTrait($world->game, 'Zealot', true);
                $cross = $world->placeCard(new _02016(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $cross->Id;
                $event->characterId = $host->Id;
                $event->theah = $world->theah;
                $cross->handleEvent($event);

                Assert::false($host->hasTrait('Zealot'), 'Zealot removed');
            },
        ];
    }
}
