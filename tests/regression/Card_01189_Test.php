<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01189;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01189a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01189b;

class Card_01189_Test extends TestCase
{
    public function name(): string
    {
        return '_01189 Point of Opportunity';
    }

    public function tests(): array
    {
        return [
            'constructs Duress Fortune CityEventCard with both City Actions' => function () {
                $poo = new _01189();
                Assert::instanceOf(CityEventCard::class, $poo, 'CityEventCard');
                Assert::instanceOf(IHasActions::class, $poo, 'actions');
                Assert::same(13, $poo->CityCardNumber, 'city #');
                Assert::true($poo->hasTrait('Duress'), 'Duress');
                Assert::true($poo->hasTrait('Fortune'), 'Fortune');
                Assert::count(2, $poo->getActions(), 'two');
                Assert::instanceOf(Action_01189a::class, $poo->getActions()[0], 'a');
                Assert::instanceOf(Action_01189b::class, $poo->getActions()[1], 'b');
            },

            'action ids stamped once placed' => function () {
                $world = new TestWorld();
                $poo = $world->placeCard(new _01189(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($poo->Id . '_Action_01189a', $poo->getActions()[0]->Id, 'a');
                Assert::same($poo->Id . '_Action_01189b', $poo->getActions()[1]->Id, 'b');
            },

            'state constants registered' => function () {
                Assert::same(4011891, States::HIGH_DRAMA_PLAYER_TURN_01189a, 'a');
                Assert::same(4011892, States::HIGH_DRAMA_PLAYER_TURN_01189b, 'b');
            },
        ];
    }
}
