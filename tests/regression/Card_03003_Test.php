<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03003;

class Card_03003_Test extends TestCase
{
    public function name(): string
    {
        return '_03003 Don Constanzo Scarpa';
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce Villain with a City Action and a City Reaction' => function () {
                $don = new _03003();
                Assert::instanceOf(Character::class, $don, 'character');
                Assert::instanceOf(IHasActions::class, $don, 'actions');
                Assert::instanceOf(IHasReactions::class, $don, 'reactions');
                Assert::same(4, $don->Resolve, 'Resolve');
                Assert::same(1, $don->Combat, 'Combat');
                Assert::same(2, $don->Finesse, 'Finesse');
                Assert::same(3, $don->Influence, 'Influence');
                Assert::true($don->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($don->hasTrait('Villain'), 'Villain');
                Assert::true($don->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($don->hasTrait('Tyrant'), 'Tyrant');
                Assert::instanceOf(Action_03003::class, $don->getActions()[0], 'action');
                Assert::instanceOf(Reaction_03003::class, $don->getReactions()[0], 'reaction');
            },

            'owns the action and reaction when placed' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($don->Id, $don->getActions()[0]->OwnerId, 'action');
                Assert::same($don->Id, $don->getReactions()[0]->OwnerId, 'reaction');
            },
        ];
    }
}
