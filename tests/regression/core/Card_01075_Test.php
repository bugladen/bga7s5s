<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_01075_Test extends TestCase
{
    public function name(): string
    {
        return '_01075 Tabard of the Fallen Musketeer';
    }

    private function equipping(TestWorld $world, _01075 $tabard, int $characterId): EventAttachmentEquipping
    {
        $event = new EventAttachmentEquipping();
        $event->attachmentId = $tabard->Id;
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Unique Montaigne FactionAttachment with Action_01075' => function () {
                $tabard = new _01075();
                Assert::instanceOf(FactionAttachment::class, $tabard, 'FactionAttachment');
                Assert::instanceOf(IHasActions::class, $tabard, 'actions');
                Assert::same(1, $tabard->WealthCost, 'WealthCost');
                Assert::same(1, $tabard->InfluenceModifier, '+1 Influence');
                Assert::same(3, $tabard->Parry, 'Parry');
                Assert::same(0, $tabard->Thrust, 'Thrust');
                Assert::true($tabard->DashedThrust, 'dashed Thrust');
                Assert::true($tabard->hasTrait('Unique'), 'Unique (Action_01069 must refuse to recover it)');
                Assert::true($tabard->hasTrait('Attire'), 'Attire');
                Assert::true($tabard->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01075::class, $tabard->getActions()[0], 'Action_01075');
            },

            'eventCheck refuses equip to a Diplomat' => function () {
                $world = new TestWorld();
                $diplomat = $world->placeCharacter(new GenericCharacter('Diplomat', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $tabard->eventCheck($this->equipping($world, $tabard, $diplomat->Id));
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'non-Diplomat only');
            },

            'eventCheck allows equip to a non-Diplomat' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_HAND, 1);

                $tabard->eventCheck($this->equipping($world, $tabard, $crew->Id));
                Assert::true(true, 'no throw');
            },

            'canAttachTo excludes Diplomats' => function () {
                $world = new TestWorld();
                $diplomat = $world->placeCharacter(new GenericCharacter('Diplomat', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = new _01075();

                Assert::false($tabard->canAttachTo($diplomat), 'Diplomat');
                Assert::true($tabard->canAttachTo($crew), 'non-Diplomat');
            },

            'equipped grants Musketeer to the wearer' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($crew->hasTrait('Musketeer'), 'precondition');

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $tabard->Id;
                $event->characterId = $crew->Id;
                $world->fireOn($tabard, $event);

                Assert::true($crew->hasTrait('Musketeer'), 'Musketeer granted');
            },

            'unequipped removes Musketeer from the wearer' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_CITY_DOCKS, 1);
                $crew->addTrait($world->game, 'Musketeer', true);

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $tabard->Id;
                $event->characterId = $crew->Id;
                $world->fireOn($tabard, $event);

                Assert::false($crew->hasTrait('Musketeer'), 'Musketeer removed');
            },

            'equip/unequip of a different attachment does not touch Musketeer' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $tabard = $world->placeCard(new _01075(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCard(new _01075(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $other->Id;
                $event->characterId = $crew->Id;
                $world->fireOn($tabard, $event);

                Assert::false($crew->hasTrait('Musketeer'), 'unchanged');
            },
        ];
    }
}
