<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03071;

class Card_03071_Test extends TestCase
{
    public function name(): string
    {
        return '_03071 Leverage';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral villainous risk' => function () {
                $card = new _03071();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(2, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(3, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Villainous'), 'Villainous');
                Assert::instanceOf(Action_03071::class, $card->getActions()[0], 'action');
            },
        ];
    }
}
