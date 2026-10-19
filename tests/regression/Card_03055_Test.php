<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03055;

class Card_03055_Test extends TestCase
{
    public function name(): string
    {
        return '_03055 Syrneth Compass';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran artifact attachment' => function () {
                $card = new _03055();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(3, $card->Riposte, 'riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Artifact'), 'Artifact');
                Assert::true($card->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Action_03055::class, $card->getActions()[0], 'action');
            },
        ];
    }
}
