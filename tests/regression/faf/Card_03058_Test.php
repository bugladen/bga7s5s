<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03058;

class Card_03058_Test extends TestCase
{
    public function name(): string
    {
        return '_03058 Courageous';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran heroic risk' => function () {
                $card = new _03058();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(1, $card->WealthCost, 'wealth');
                Assert::same(0, $card->Riposte, 'riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Heroic'), 'Heroic');
                Assert::instanceOf(Action_03058::class, $card->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03058::class, $card->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
