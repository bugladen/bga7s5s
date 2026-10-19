<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03060;

class Card_03060_Test extends TestCase
{
    public function name(): string
    {
        return "_03060 Matushka's Song";
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran sorcery risk' => function () {
                $card = new _03060();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(1, $card->WealthCost, 'wealth');
                Assert::false($card->WillEngage, 'will engage');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Dar Matushki'), 'Dar Matushki');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Action_03060::class, $card->getActions()[0], 'action');
                Assert::instanceOf(Maneuver_03060::class, $card->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
