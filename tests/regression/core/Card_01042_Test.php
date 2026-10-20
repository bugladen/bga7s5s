<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;

class Card_01042_Test extends TestCase
{
    public function name(): string
    {
        return '_01042 Terrell Brandt';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Technique_PlusOneThrust id Technique_01042' => function () {
                $terrell = new _01042();
                Assert::instanceOf(IHasTechniques::class, $terrell, 'techniques');
                Assert::same(5, $terrell->Resolve, 'Resolve');
                Assert::same(3, $terrell->Combat, 'Combat');
                Assert::same(1, $terrell->Finesse, 'Finesse');
                Assert::same(1, $terrell->Influence, 'Influence');
                Assert::true($terrell->hasTrait('Duelist'), 'Duelist');
                Assert::true($terrell->hasFaction('Eisen'), 'Eisen');
                $technique = $terrell->getTechniques()[0];
                Assert::instanceOf(Technique_PlusOneThrust::class, $technique, 'PlusOneThrust');
                Assert::same('Technique_01042', $technique->Id, 'technique id');
            },
        ];
    }
}
