<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class Card_01038_Test extends TestCase
{
    public function name(): string
    {
        return '_01038 Otto Streit';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Action_01038' => function () {
                $otto = new _01038();
                Assert::instanceOf(IHasActions::class, $otto, 'actions');
                Assert::same(4, $otto->Resolve, 'Resolve');
                Assert::same(1, $otto->Combat, 'Combat');
                Assert::same(2, $otto->Finesse, 'Finesse');
                Assert::same(2, $otto->Influence, 'Influence');
                Assert::true($otto->hasTrait('Academic'), 'Academic');
                Assert::true($otto->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01038::class, $otto->getActions()[0], 'Action_01038');
            },
        ];
    }
}
