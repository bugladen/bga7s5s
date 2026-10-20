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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01192;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01192;

class Card_01192_Test extends TestCase
{
    public function name(): string
    {
        return '_01192 Gustavo';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable CityCharacter with Action_01192' => function () {
                $gustavo = new _01192();
                Assert::instanceOf(CityCharacter::class, $gustavo, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $gustavo, 'actions');
                Assert::same(3, $gustavo->Resolve, 'Resolve');
                Assert::same(0, $gustavo->Combat, 'Combat');
                Assert::same(3, $gustavo->Finesse, 'Finesse');
                Assert::same(2, $gustavo->Influence, 'Influence');
                Assert::same(4, $gustavo->WealthCost, 'wealth');
                Assert::same(16, $gustavo->CityCardNumber, 'city #');
                Assert::true($gustavo->Negotiable, 'Negotiable');
                Assert::true($gustavo->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($gustavo->hasTrait('Academic'), 'Academic');
                Assert::true($gustavo->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($gustavo->hasTrait('Villain'), 'Villain');
                Assert::true($gustavo->hasTrait('Castille'), 'Castille');
                Assert::instanceOf(Action_01192::class, $gustavo->getActions()[0], 'Action');
            },

            'owns Action_01192 when placed' => function () {
                $world = new TestWorld();
                $gustavo = $world->placeCharacter(new _01192(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $gustavo->getActions(), 'one');
                Assert::same($gustavo->Id, $gustavo->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(401192, States::HIGH_DRAMA_PLAYER_TURN_01192, '01192');
                Assert::same(4011922, States::HIGH_DRAMA_PLAYER_TURN_01192_2, '01192_2');
                Assert::same(4011923, States::HIGH_DRAMA_PLAYER_TURN_01192_3, '01192_3');
            },
        ];
    }
}
