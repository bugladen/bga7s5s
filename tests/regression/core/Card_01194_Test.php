<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01194;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01194;

class Card_01194_Test extends TestCase
{
    public function name(): string
    {
        return '_01194 Adelheide Schmidt';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable CityCharacter with Action_01194' => function () {
                $adelheide = new _01194();
                Assert::instanceOf(CityCharacter::class, $adelheide, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $adelheide, 'actions');
                Assert::same(4, $adelheide->Resolve, 'Resolve');
                Assert::same(2, $adelheide->Combat, 'Combat');
                Assert::same(2, $adelheide->Finesse, 'Finesse');
                Assert::same(0, $adelheide->Influence, 'Influence');
                Assert::true($adelheide->DashedInfluence, 'dashed Influence');
                Assert::same(4, $adelheide->WealthCost, 'wealth');
                Assert::same(18, $adelheide->CityCardNumber, 'city #');
                Assert::true($adelheide->Negotiable, 'Negotiable');
                Assert::true($adelheide->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($adelheide->hasTrait('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01194::class, $adelheide->getActions()[0], 'Action');
            },

            'owns Action_01194 when placed' => function () {
                $world = new TestWorld();
                $adelheide = $world->placeCharacter(new _01194(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $adelheide->getActions(), 'one');
                Assert::same($adelheide->Id, $adelheide->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(401194, States::HIGH_DRAMA_PLAYER_TURN_01194, '01194');
                Assert::same(4011942, States::HIGH_DRAMA_PLAYER_TURN_01194_2, '01194_2');
            },
        ];
    }
}
