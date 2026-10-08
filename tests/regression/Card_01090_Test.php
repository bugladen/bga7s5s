<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01090_Test extends TestCase
{
    public function name(): string
    {
        return '_01090 Lorenzo de Zepeda';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Duelist Scoundrel with Action, Reaction and Technique' => function () {
                $lorenzo = new _01090();
                Assert::instanceOf(IHasActions::class, $lorenzo, 'actions');
                Assert::instanceOf(IHasReactions::class, $lorenzo, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $lorenzo, 'techniques');
                Assert::same(4, $lorenzo->Resolve, 'Resolve');
                Assert::same(2, $lorenzo->Combat, 'Combat');
                Assert::same(4, $lorenzo->Finesse, 'Finesse');
                Assert::same(1, $lorenzo->Influence, 'Influence');
                Assert::true($lorenzo->hasTrait('Duelist'), 'Duelist');
                Assert::true($lorenzo->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($lorenzo->hasFaction('Castille'), 'Castille');
                Assert::instanceOf(Action_01090::class, $lorenzo->getActions()[0], 'Action_01090');
                Assert::instanceOf(Reaction_01090::class, $lorenzo->getReactions()[0], 'Reaction_01090');
                Assert::instanceOf(Technique_01090::class, $lorenzo->getTechniques()[0], 'Technique_01090');
            },

            // WHY: Action_01090 looks up the Reaction by "<ownerId>_Reaction_01090"; ids must be re-stamped by setId.
            'ability ids are stamped with the owner id once placed' => function () {
                $world = new TestWorld();
                $lorenzo = $world->placeCharacter(new _01090(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($lorenzo->Id . '_Reaction_01090', $lorenzo->getReactions()[0]->Id, 'reaction id');
                Assert::same($lorenzo->Id . '_Action_01090', $lorenzo->getActions()[0]->Id, 'action id');
                Assert::same($lorenzo->Id . '_Technique_01090', $lorenzo->getTechniques()[0]->Id, 'technique id');
                Assert::true($lorenzo->getReactionById($lorenzo->Id . '_Reaction_01090') !== null, 'lookup works');
            },
        ];
    }
}
