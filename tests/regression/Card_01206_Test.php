<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01206;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01206;

class Card_01206_Test extends TestCase
{
    public function name(): string
    {
        return '_01206 Captain\'s Coat';
    }

    public function tests(): array
    {
        return [
            'constructs Attire Coat CityAttachment with Action_01206' => function () {
                $coat = new _01206();
                Assert::instanceOf(CityAttachment::class, $coat, 'CityAttachment');
                Assert::instanceOf(IHasActions::class, $coat, 'actions');
                Assert::same(3, $coat->WealthCost, 'Wealth');
                Assert::same(30, $coat->CityCardNumber, 'city #');
                Assert::same(0, $coat->ResolveModifier, 'Resolve');
                Assert::same(0, $coat->CombatModifier, 'Combat');
                Assert::same(0, $coat->FinesseModifier, 'Finesse');
                Assert::same(0, $coat->InfluenceModifier, 'Influence');
                Assert::true($coat->hasTrait('Attire'), 'Attire');
                Assert::true($coat->hasTrait('Coat'), 'Coat');
                Assert::instanceOf(Action_01206::class, $coat->getActions()[0], 'Action');
            },

            'owns Action_01206 when placed' => function () {
                $world = new TestWorld();
                $coat = $world->placeCard(new _01206(), Game::LOCATION_HAND, 1);
                Assert::count(1, $coat->getActions(), 'one');
                Assert::same($coat->Id, $coat->getActions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
