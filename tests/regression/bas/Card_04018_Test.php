<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04018;

class Card_04018_Test extends TestCase
{
    public function name(): string
    {
        return '_04018 Seek Each Devil';
    }

    public function tests(): array
    {
        return [
            'is a hunt risk that costs one less for an academic or hunter' => function () {
                $card = new _04018();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(4, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::count(1, $card->getActions(), 'action');

                $world = new TestWorld();
                $risk = $world->placeCard($card, Game::LOCATION_HAND, 1);
                $hunter = $world->placeCharacter(new GenericCharacter('Hunter', ['Hunter']), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04018 $action */
                $action = $risk->getActions()[0];
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $hunter, $action, $explanations), 'hunter');
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $hero, $action, $explanations), 'hero');
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'no performer');
            },
        ];
    }
}
