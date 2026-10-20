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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01185;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01185;

class Card_01185_Test extends TestCase
{
    public function name(): string
    {
        return '_01185 Risky Undertaking';
    }

    public function tests(): array
    {
        return [
            'constructs Discovery Explorer\'s Society Fortune CityEvent with Action_01185' => function () {
                $card = new _01185();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(9, $card->CityCardNumber, 'city #');
                Assert::true($card->hasTrait('Discovery'), 'Discovery');
                Assert::true($card->hasTrait("Explorer's Society"), "Explorer's Society");
                Assert::true($card->hasTrait('Fortune'), 'Fortune');
                Assert::instanceOf(Action_01185::class, $card->getActions()[0], 'Action');
                Assert::instanceOf(EventCityAction::class, $card->getActions()[0], 'EventCityAction');
            },

            'owns Action_01185 when placed' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01185(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $card->getActions(), 'one');
                Assert::same($card->Id, $card->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(401185, States::HIGH_DRAMA_PLAYER_TURN_01185, 'state');
            },
        ];
    }
}
