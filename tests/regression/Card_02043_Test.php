<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043b;

class Card_02043_Test extends TestCase
{
    public function name(): string
    {
        return '_02043 Miyato and Ota';
    }

    public function tests(): array
    {
        return [
            // WHY: IHasManeuvers is how a copied maneuver is registered on the twins. They print no maneuvers.
            'constructs Ussura twins with dashed Influence and two Techniques' => function () {
                $twins = new _02043();
                Assert::instanceOf(Character::class, $twins, 'Character');
                Assert::instanceOf(IHasTechniques::class, $twins, 'techniques');
                Assert::instanceOf(IHasManeuvers::class, $twins, 'maneuver host');
                Assert::same('Two of a Kind', $twins->Title, 'title');
                Assert::same(5, $twins->Resolve, 'Resolve');
                Assert::same(3, $twins->Combat, 'Combat');
                Assert::same(2, $twins->Finesse, 'Finesse');
                Assert::same(0, $twins->Influence, 'Influence');
                Assert::true($twins->DashedInfluence, 'dashed Influence');
                Assert::true($twins->hasFaction('Ussura'), 'Ussura');
                Assert::true($twins->hasTrait('Twins'), 'Twins');
                Assert::true($twins->hasTrait('Duelist'), 'Duelist');
                Assert::true($twins->hasTrait('Fusō'), 'Fusō');
                Assert::count(0, $twins->getManeuvers(), 'no printed maneuvers');
                Assert::instanceOf(Technique_02043a::class, $twins->getTechniques()[0], 'copy');
                Assert::instanceOf(Technique_02043b::class, $twins->getTechniques()[1], 'flourish');
            },

            'owns both techniques when placed' => function () {
                $world = new TestWorld();
                $twins = $world->placeCharacter(new _02043(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($twins->Id, $twins->getTechniques()[0]->OwnerId, 'a');
                Assert::same($twins->Id, $twins->getTechniques()[1]->OwnerId, 'b');
            },
        ];
    }
}
