<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01198;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01198;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_01198_Test extends TestCase
{
    public function name(): string
    {
        return '_01198 Guild Triskelion';
    }

    public function tests(): array
    {
        return [
            'constructs Bureaucracy Trinket CityAttachment with +1 Finesse' => function () {
                $tri = new _01198();
                Assert::instanceOf(CityAttachment::class, $tri, 'CityAttachment');
                Assert::instanceOf(IHasActions::class, $tri, 'actions');
                Assert::same(2, $tri->WealthCost, 'Wealth');
                Assert::same(1, $tri->FinesseModifier, '+1 Finesse');
                Assert::same(0, $tri->CombatModifier, 'Combat');
                Assert::true($tri->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($tri->hasTrait('Trinket'), 'Trinket');
                Assert::instanceOf(Action_01198::class, $tri->getActions()[0], 'Action');
            },

            'owns Action_01198 when placed' => function () {
                $world = new TestWorld();
                $tri = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $tri->getActions(), 'one');
                Assert::same($tri->Id, $tri->getActions()[0]->OwnerId, 'owner');
            },

            'equipped grants Duelist to the wearer' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $tri = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($host->hasTrait('Duelist'), 'precondition');

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $tri->Id;
                $event->characterId = $host->Id;
                $world->fireOn($tri, $event);

                Assert::true($host->hasTrait('Duelist'), 'Duelist granted');
            },

            'unequipped removes Duelist from the wearer' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $tri = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);
                $host->addTrait($world->game, 'Duelist', true);

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $tri->Id;
                $event->characterId = $host->Id;
                $world->fireOn($tri, $event);

                Assert::false($host->hasTrait('Duelist'), 'Duelist removed');
            },

            'equip of a different attachment does not grant Duelist' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $tri = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $other->Id;
                $event->characterId = $host->Id;
                $world->fireOn($tri, $event);

                Assert::false($host->hasTrait('Duelist'), 'unchanged');
            },
        ];
    }
}
