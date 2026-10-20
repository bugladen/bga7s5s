<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01191;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01191;

class Card_01191_Test extends TestCase
{
    public function name(): string
    {
        return '_01191 Duckfoot Pistol';
    }

    public function tests(): array
    {
        return [
            // WHY: CityAttachment — no FactionAttachment Riposte/Parry/Thrust pin.
            'constructs CityAttachment Weapon with Action_01191' => function () {
                $pistol = new _01191();
                Assert::instanceOf(CityAttachment::class, $pistol, 'CityAttachment');
                Assert::instanceOf(IHasActions::class, $pistol, 'actions');
                Assert::same(1, $pistol->WealthCost, 'wealth');
                Assert::same(15, $pistol->CityCardNumber, 'city #');
                Assert::same(0, $pistol->CombatModifier, 'combat');
                Assert::true($pistol->hasTrait('Weapon'), 'Weapon');
                Assert::true($pistol->hasTrait('Ranged'), 'Ranged');
                Assert::true($pistol->hasTrait('Pistol'), 'Pistol');
                Assert::instanceOf(Action_01191::class, $pistol->getActions()[0], 'Action');
            },

            'owns Action_01191 when placed' => function () {
                $world = new TestWorld();
                $pistol = $world->placeCard(new _01191(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $pistol->getActions(), 'one');
                Assert::same($pistol->Id, $pistol->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
