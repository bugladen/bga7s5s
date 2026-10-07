<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01039_Test extends TestCase
{
    public function name(): string
    {
        return '_01039 Philip Hase';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Reaction_01039 and Technique_01039' => function () {
                $philip = new _01039();
                Assert::instanceOf(IHasReactions::class, $philip, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $philip, 'techniques');
                Assert::same(5, $philip->Resolve, 'Resolve');
                Assert::same(3, $philip->Combat, 'Combat');
                Assert::same(1, $philip->Finesse, 'Finesse');
                Assert::same(2, $philip->Influence, 'Influence');
                Assert::true($philip->hasTrait('Hunter'), 'Hunter');
                Assert::true($philip->hasTrait('Academic'), 'Academic');
                Assert::true($philip->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Technique_01039::class, $philip->getTechniques()[0], 'Technique_01039');
                Assert::instanceOf(Reaction_01039::class, $philip->getReactions()[0], 'Reaction_01039');
            },
        ];
    }
}
