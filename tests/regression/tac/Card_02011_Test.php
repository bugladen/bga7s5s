<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02011;

class Card_02011_Test extends TestCase
{
    public function name(): string
    {
        return '_02011 Katain DeWinter';
    }

    public function tests(): array
    {
        return [
            // WHY: Implements IHasActions/IHasManeuvers as copy hosts only — Actions/Maneuvers
            // arrays stay empty; Reaction_02011 + Technique_02011 are the printed abilities.
            'constructs Eisen Character with Reaction and Technique, empty Actions/Maneuvers' => function () {
                $katain = new _02011();
                Assert::instanceOf(Character::class, $katain, 'Character');
                Assert::instanceOf(IHasActions::class, $katain, 'actions host');
                Assert::instanceOf(IHasManeuvers::class, $katain, 'maneuvers host');
                Assert::instanceOf(IHasReactions::class, $katain, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $katain, 'techniques');
                Assert::same(4, $katain->Resolve, 'Resolve');
                Assert::same(2, $katain->Combat, 'Combat');
                Assert::same(2, $katain->Finesse, 'Finesse');
                Assert::same(1, $katain->Influence, 'Influence');
                Assert::true($katain->hasTrait('Knight'), 'Knight');
                Assert::true($katain->hasTrait('Academic'), 'Academic');
                Assert::true($katain->hasTrait('Zealot'), 'Zealot');
                Assert::true($katain->hasTrait('Montaigne'), 'Montaigne');
                Assert::true($katain->hasFaction('Eisen'), 'Eisen');
                Assert::same([], $katain->getActions(), 'no printed actions');
                Assert::same([], $katain->getManeuvers(), 'no printed maneuvers');
                Assert::instanceOf(Reaction_02011::class, $katain->getReactions()[0], 'Reaction');
                Assert::instanceOf(Technique_02011::class, $katain->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $katain = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($katain->Id, $katain->getReactions()[0]->OwnerId, 'reaction owner');
                Assert::same($katain->Id, $katain->getTechniques()[0]->OwnerId, 'technique owner');
            },

            'state constant registered' => function () {
                Assert::same(52102011, States::DUEL_CHOOSE_TECHNIQUE_02011, '02011');
            },
        ];
    }
}
