<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd03;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd03;

class Card_03cd03_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd03 Chance Meeting';
    }

    public function tests(): array
    {
        return [
            'constructs an Ad Hoc Camaraderie city event with the muster Action' => function () {
                $card = new _03cd03();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(3, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($card->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::count(1, $card->getActions(), 'one Action');
                Assert::instanceOf(Action_03cd03::class, $card->getActions()[0], 'Action_03cd03');
            },
        ];
    }
}
