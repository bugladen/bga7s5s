<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03002;

class Card_03002_Test extends TestCase
{
    public function name(): string
    {
        return '_03002 Aja';
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce Assassin with a City Action and a Gambling Technique' => function () {
                $aja = new _03002();
                Assert::instanceOf(Character::class, $aja, 'character');
                Assert::instanceOf(IHasActions::class, $aja, 'actions');
                Assert::same(5, $aja->Resolve, 'Resolve');
                Assert::same(2, $aja->Combat, 'Combat');
                Assert::same(4, $aja->Finesse, 'Finesse');
                Assert::same(0, $aja->Influence, 'Influence');
                Assert::true($aja->DashedInfluence, 'dashed Influence');
                Assert::true($aja->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($aja->hasTrait('Assassin'), 'Assassin');
                Assert::true($aja->hasTrait('Duelist'), 'Duelist');
                Assert::true($aja->hasTrait('Spy'), 'Spy');
                Assert::instanceOf(Action_03002::class, $aja->getActions()[0], 'action');
                Assert::instanceOf(Technique_03002::class, $aja->getTechniques()[0], 'technique');
            },

            'owns the action and technique when placed' => function () {
                $world = new TestWorld();
                $aja = $world->placeCharacter(new _03002(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($aja->Id, $aja->getActions()[0]->OwnerId, 'action');
                Assert::same($aja->Id, $aja->getTechniques()[0]->OwnerId, 'technique');
            },
        ];
    }
}
