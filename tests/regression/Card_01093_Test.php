<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01093_Test extends TestCase
{
    public function name(): string
    {
        return '_01093 Maya de La Rioja';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Duelist Pirate with Action and Technique' => function () {
                $maya = new _01093();
                Assert::instanceOf(IHasActions::class, $maya, 'actions');
                Assert::instanceOf(IHasTechniques::class, $maya, 'techniques');
                Assert::false($maya instanceof IHasReactions, 'no reactions');
                Assert::same(5, $maya->Resolve, 'Resolve');
                Assert::same(3, $maya->Combat, 'Combat');
                Assert::same(2, $maya->Finesse, 'Finesse');
                Assert::same(1, $maya->Influence, 'Influence');
                Assert::true($maya->hasTrait('Duelist'), 'Duelist');
                Assert::true($maya->hasTrait('Pirate'), 'Pirate');
                Assert::true($maya->hasFaction('Castille'), 'Castille');
                Assert::instanceOf(Action_01093::class, $maya->getActions()[0], 'Action_01093');
                Assert::instanceOf(Technique_01093::class, $maya->getTechniques()[0], 'Technique_01093');
            },

            // WHY: Reaction_01090 (Lorenzo) only offers its override for abilities carrying this marker.
            'her Action carries the first-player-dependent marker (Lorenzo hook)' => function () {
                Assert::instanceOf(IAbilityThatDependsOnNotBeingFirstPlayer::class, (new _01093())->getActions()[0], 'marker');
            },

            'her Technique is not first-player dependent' => function () {
                Assert::false((new _01093())->getTechniques()[0] instanceof IAbilityThatDependsOnNotBeingFirstPlayer, 'no marker');
            },

            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $maya = $world->placeCharacter(new _01093(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($maya->Id . '_Action_01093', $maya->getActions()[0]->Id, 'action id');
                Assert::same($maya->Id . '_Technique_01093', $maya->getTechniques()[0]->Id, 'technique id');
            },
        ];
    }
}
