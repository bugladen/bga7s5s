<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;

class Card_04054_Test extends TestCase
{
    public function name(): string
    {
        return '_04054 Sabre';
    }

    public function tests(): array
    {
        return [
            'is a sword' => function () {
                $card = new _04054();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Weapon'), 'Weapon');
                Assert::true($card->hasTrait('Melee'), 'Melee');
                Assert::true($card->hasTrait('Sword'), 'Sword');
                Assert::count(2, $card->getTechniques(), 'techniques');
                Assert::same('Technique_04054a', $card->getTechniques()[0]->ClassId, 'distinct thrust id');
            },
        ];
    }
}
