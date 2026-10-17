<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd20;

class Card_03cd20_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd20 Early Morning Arrangements';
    }

    public function tests(): array
    {
        return [
            'constructs a Prepared Timely Punctual city event with Action and Reaction' => function () {
                $card = new _03cd20();
                Assert::instanceOf(CityEventCard::class, $card, 'CityEventCard');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(20, $card->CityCardNumber, 'city number');
                Assert::true($card->hasTrait('Prepared'), 'Prepared');
                Assert::true($card->hasTrait('Timely'), 'Timely');
                Assert::true($card->hasTrait('Punctual'), 'Punctual');
                Assert::count(1, $card->getActions(), 'one Action');
                Assert::instanceOf(Action_03cd20::class, $card->getActions()[0], 'Action_03cd20');
                Assert::count(1, $card->getReactions(), 'one Reaction');
                Assert::instanceOf(Reaction_03cd20::class, $card->getReactions()[0], 'Reaction_03cd20');
            },
        ];
    }
}
