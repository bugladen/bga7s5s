<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04011;

class Card_04011_Test extends TestCase
{
    public function name(): string
    {
        return '_04011 Hans Offenheim';
    }

    public function tests(): array
    {
        return [
            'is an Eisen diplomat' => function () {
                $card = new _04011();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(3, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Noble'), 'Noble');
                Assert::true($card->hasTrait('Eisen'), 'Eisen');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: En Garde raises the recruit cost of an opposing performer at his city
            // location. The discount is negative. Home and his own performer do not pay it.
            'opposing recruiters pay one more while he is en garde' => function () {
                $world = new TestWorld();
                $hans = $world->placeCharacter(new _04011(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $explanations = [];
                Assert::same(-1, $hans->getParleyDiscount($world->theah, $foe, true, $explanations), 'plus one cost');

                $hans->Engaged = true;
                Assert::same(0, $hans->getParleyDiscount($world->theah, $foe, true, $explanations), 'engaged');
                $hans->Engaged = false;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::same(0, $hans->getParleyDiscount($world->theah, $foe, true, $explanations), 'home performer');
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $foe->ControllerId = 1;
                Assert::same(0, $hans->getParleyDiscount($world->theah, $foe, true, $explanations), 'his own performer');
            },
        ];
    }
}
