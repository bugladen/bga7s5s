<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01070;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01070;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;

class Card_01070_Test extends TestCase
{
    public function name(): string
    {
        return '_01070 Urraca de Murrieta';
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Diplomat Character with Reaction_01070' => function () {
                $urraca = new _01070();
                Assert::instanceOf(Character::class, $urraca, 'Character');
                Assert::instanceOf(IHasReactions::class, $urraca, 'reactions');
                Assert::same(5, $urraca->Resolve, 'Resolve');
                Assert::same(1, $urraca->Combat, 'Combat');
                Assert::same(2, $urraca->Finesse, 'Finesse');
                Assert::same(2, $urraca->Influence, 'Influence');
                Assert::true($urraca->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($urraca->hasTrait('Castille'), 'Castille');
                Assert::true($urraca->hasFaction('Montaigne'), 'Montaigne');
                Assert::count(1, $urraca->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01070::class, $urraca->getReactions()[0], 'Reaction_01070');
            },
        ];
    }
}
