<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03064;

class Card_03064_Test extends TestCase
{
    public function name(): string
    {
        return '_03064 Harpoon';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral harpoon' => function () {
                $card = new _03064();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasTechniques::class, $card, 'techniques');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::true($card->DashedParry, 'dashed parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Weapon'), 'Weapon');
                Assert::true($card->hasTrait('Ranged'), 'Ranged');
                Assert::true($card->hasTrait('Harpoon'), 'Harpoon');
                Assert::instanceOf(Technique_03064::class, $card->getTechniques()[0], 'technique');
            },
        ];
    }
}
