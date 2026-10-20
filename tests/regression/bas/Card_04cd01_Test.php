<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01b;

class Card_04cd01_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd01 Penya';
    }

    public function tests(): array
    {
        return [
            'is a unique swindler guide with an engage move and a sink-to-play city action' => function () {
                $card = new _04cd01();
                Assert::instanceOf(CityAttachment::class, $card, 'CityAttachment');
                Assert::same(1, $card->CityCardNumber, 'city number');
                Assert::same(2, $card->WealthCost, 'wealth');
                Assert::true($card->hasTrait('Swindler'), 'Swindler');
                Assert::true($card->hasTrait('Guide'), 'Guide');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_04cd01::class, $card->getActions()[0], 'engage move');
                Assert::instanceOf(Action_04cd01b::class, $card->getActions()[1], 'sink to play');
            },
        ];
    }
}
