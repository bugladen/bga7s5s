<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Quack;

class Card_05Quack_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Quack';
    }

    public function tests(): array
    {
        return [
            'wires Action and Covert Reaction' => function () {
                $card = new _05Quack();
                Assert::same('Takama Siad', $card->Name, 'name');
                Assert::same(24, $card->CardNumber, 'card number');
                Assert::instanceOf(Action_05Quack::class, $card->getActions()[0], 'action');
                Assert::instanceOf(Reaction_05Quack::class, $card->getReactions()[0], 'reaction');
            },
        ];
    }
}
