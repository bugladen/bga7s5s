<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01013_Test extends TestCase
{
    public function name(): string
    {
        return '_01013 Vissenta Scarpa';
    }

    public function tests(): array
    {
        return [
            'constructs with Technique_01013, Reaction_01013, and stats' => function () {
                $vissenta = new _01013();
                Assert::instanceOf(IHasTechniques::class, $vissenta, 'techniques');
                Assert::instanceOf(IHasReactions::class, $vissenta, 'reactions');
                Assert::same(4, $vissenta->Resolve, 'Resolve');
                Assert::same(3, $vissenta->Combat, 'Combat');
                Assert::same(2, $vissenta->Finesse, 'Finesse');
                Assert::same(1, $vissenta->Influence, 'Influence');
                Assert::true($vissenta->hasTrait('Hero'), 'Hero');
                Assert::true($vissenta->hasTrait('Duelist'), 'Duelist');
                Assert::true($vissenta->hasTrait('Vodacce'), 'Vodacce');
                Assert::instanceOf(Technique_01013::class, $vissenta->getTechniques()[0], 'Technique_01013');
                Assert::instanceOf(Reaction_01013::class, $vissenta->getReactions()[0], 'Reaction_01013');
            },

            'state constant for technique choose' => function () {
                Assert::same(52101013, States::DUEL_CHOOSE_TECHNIQUE_01013, 'state id');
            },
        ];
    }
}
