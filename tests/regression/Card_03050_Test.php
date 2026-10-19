<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03050;

class Card_03050_Test extends TestCase
{
    public function name(): string
    {
        return '_03050 Mōri Daichi';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran duelist with a technique' => function () {
                $daichi = new _03050();
                Assert::instanceOf(Character::class, $daichi, 'character');
                Assert::same(4, $daichi->Resolve, 'resolve');
                Assert::same(2, $daichi->Combat, 'combat');
                Assert::same(3, $daichi->Finesse, 'finesse');
                Assert::same(2, $daichi->Influence, 'influence');
                Assert::true($daichi->hasFaction('Ussura'), 'Ussura');
                Assert::true($daichi->hasTrait('Duelist'), 'Duelist');
                Assert::true($daichi->hasTrait('Kannushi'), 'Kannushi');
                Assert::true($daichi->hasTrait('Fusō'), 'Fusō');
                Assert::instanceOf(Technique_03050::class, $daichi->getTechniques()[0], 'technique');
            },

            // WHY: This is a character-identity gate, not a challenge type. Greater is
            // strict. It applies when Daichi is either the challenger or the defender.
            'greater Combat blocks a refusal in either direction' => function () {
                $daichi = new _03050();
                $other = new GenericCharacter('Other');
                $other->ModifiedCombat = 3;
                $daichi->ModifiedCombat = 2;
                Assert::true(_03050::challengeRefusalBlocked($daichi, $other), 'he issued it');
                Assert::true(_03050::challengeRefusalBlocked($other, $daichi), 'he was issued it');

                $other->ModifiedCombat = 2;
                Assert::false(_03050::challengeRefusalBlocked($daichi, $other), 'equal may refuse');
                Assert::false(_03050::challengeRefusalBlocked($other, $daichi), 'equal the other way');

                $bystander = new GenericCharacter('Bystander');
                $bystander->ModifiedCombat = 9;
                Assert::false(_03050::challengeRefusalBlocked($other, $bystander), 'neither is Daichi');
            },
        ];
    }
}
