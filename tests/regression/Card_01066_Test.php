<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01066;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01066_Test extends TestCase
{
    public function name(): string
    {
        return '_01066 Horatio Lockwood';
    }

    public function tests(): array
    {
        return [
            'constructs Duelist Scoundrel with printed stats' => function () {
                $horatio = new _01066();
                Assert::same(5, $horatio->Resolve, 'Resolve');
                Assert::same(3, $horatio->Combat, 'Combat');
                Assert::same(3, $horatio->Finesse, 'Finesse');
                Assert::same(0, $horatio->Influence, 'Influence');
                Assert::true($horatio->DashedInfluence, 'dashed Influence');
                Assert::true($horatio->hasTrait('Duelist'), 'Duelist');
                Assert::true($horatio->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($horatio->hasFaction('Montaigne'), 'Montaigne');
            },

            'has Reaction_01066 and Technique_01066' => function () {
                $horatio = new _01066();
                Assert::instanceOf(IHasReactions::class, $horatio, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $horatio, 'techniques');
                Assert::instanceOf(Reaction_01066::class, $horatio->getReactions()[0], 'Reaction_01066');
                Assert::instanceOf(Technique_01066::class, $horatio->getTechniques()[0], 'Technique_01066');
            },
        ];
    }
}
