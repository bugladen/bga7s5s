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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01205;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01205;

class Card_01205_Test extends TestCase
{
    public function name(): string
    {
        return '_01205 Giacinto';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary CityCharacter with Action_01205' => function () {
                $giacinto = new _01205();
                Assert::instanceOf(CityCharacter::class, $giacinto, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $giacinto, 'actions');
                Assert::true($giacinto->Negotiable, 'Negotiable');
                Assert::same(3, $giacinto->Resolve, 'Resolve');
                Assert::same(0, $giacinto->Combat, 'Combat');
                Assert::same(1, $giacinto->Finesse, 'Finesse');
                Assert::same(2, $giacinto->Influence, 'Influence');
                Assert::same(5, $giacinto->WealthCost, 'Wealth');
                Assert::same(29, $giacinto->CityCardNumber, 'city #');
                Assert::true($giacinto->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($giacinto->hasTrait('Academic'), 'Academic');
                Assert::true($giacinto->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($giacinto->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($giacinto->hasTrait('Castille'), 'Castille');
                Assert::instanceOf(Action_01205::class, $giacinto->getActions()[0], 'Action');
            },

            'owns Action_01205 when placed' => function () {
                $world = new TestWorld();
                $giacinto = $world->placeCharacter(new _01205(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $giacinto->getActions(), 'one');
                Assert::same($giacinto->Id, $giacinto->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(401205, States::HIGH_DRAMA_PLAYER_TURN_01205, 'step 1');
                Assert::same(4012052, States::HIGH_DRAMA_PLAYER_TURN_01205_2, 'step 2');
            },
        ];
    }
}
