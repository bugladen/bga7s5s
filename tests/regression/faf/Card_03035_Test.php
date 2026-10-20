<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03035;

class Card_03035_Test extends TestCase
{
    public function name(): string
    {
        return '_03035 Loyal';
    }

    public function tests(): array
    {
        return [
            'constructs a camaraderie risk with a reaction and a maneuver' => function () {
                $risk = new _03035();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasReactions::class, $risk, 'reactions');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(1, $risk->Riposte, 'riposte');
                Assert::same(1, $risk->Parry, 'parry');
                Assert::same(1, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Hubris'), 'Hubris');
                Assert::true($risk->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Reaction_03035::class, $risk->getReactions()[0], 'reaction');
                Assert::instanceOf(Maneuver_03035::class, $risk->getManeuvers()[0], 'maneuver');
            },
        ];
    }
}
