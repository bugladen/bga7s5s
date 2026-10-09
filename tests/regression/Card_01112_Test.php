<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01112;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class Card_01112_Test extends TestCase
{
    public function name(): string
    {
        return '_01112 Carnaval';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Risk with City Action and Action' => function () {
                $card = new _01112();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::true($card->hasFaction('Castille'), 'Castille');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $card->Parry, 'Parry');
                Assert::same(2, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Revelry'), 'Revelry');
                Assert::count(2, $card->getActions(), 'two actions');
                Assert::instanceOf(Action_01112a::class, $card->getActions()[0], '01112a');
                Assert::instanceOf(Action_01112b::class, $card->getActions()[1], '01112b');
            },
        ];
    }
}
