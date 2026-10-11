<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01187;

class Card_01187_Test extends TestCase
{
    public function name(): string
    {
        return '_01187 Smuggled Item';
    }

    public function tests(): array
    {
        return [
            'constructs Artifact CityAttachment with Action_01187' => function () {
                $item = new _01187();
                Assert::instanceOf(CityAttachment::class, $item, 'CityAttachment');
                Assert::instanceOf(IHasActions::class, $item, 'actions');
                Assert::same(1, $item->WealthCost, 'Wealth');
                Assert::same(11, $item->CityCardNumber, 'city #');
                Assert::same(1, $item->InfluenceModifier, 'Influence');
                Assert::same(0, $item->CombatModifier, 'Combat');
                Assert::true($item->hasTrait('Artifact'), 'Artifact');
                Assert::instanceOf(Action_01187::class, $item->getActions()[0], 'Action');
            },

            'owns Action_01187 when placed' => function () {
                $world = new TestWorld();
                $item = $world->placeCard(new _01187(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $item->getActions(), 'one');
                Assert::same($item->Id, $item->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
