<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01096;

class Card_01096_Test extends TestCase
{
    public function name(): string
    {
        return '_01096 Raton';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Pirate Scoundrel Thief with 4/3/3/1 stats' => function () {
                $raton = new _01096();
                Assert::same(4, $raton->Resolve, 'Resolve');
                Assert::same(3, $raton->Combat, 'Combat');
                Assert::same(3, $raton->Finesse, 'Finesse');
                Assert::same(1, $raton->Influence, 'Influence');
                Assert::true($raton->hasFaction('Castille'), 'Castille');
                Assert::true($raton->hasTrait('Pirate'), 'Pirate');
                Assert::true($raton->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($raton->hasTrait('Thief'), 'Thief');
            },

            'exposes exactly one Action_01096 and one Technique_01096' => function () {
                $raton = new _01096();
                Assert::count(1, $raton->getActions(), 'one Action');
                Assert::instanceOf(Action_01096::class, $raton->getActions()[0], 'Action type');
                Assert::count(1, $raton->getTechniques(), 'one Technique');
                Assert::instanceOf(Technique_01096::class, $raton->getTechniques()[0], 'Technique type');
            },

            'placed in the world, abilities are owned by the character' => function () {
                $world = new TestWorld();
                $raton = $world->placeCharacter(new _01096(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($raton->Id, $raton->getActions()[0]->OwnerId, 'Action owner');
                Assert::same($raton->Id, $raton->getTechniques()[0]->OwnerId, 'Technique owner');
            },

            'Technique starts disarmed with no adversary' => function () {
                $technique = (new _01096())->getTechniques()[0];
                Assert::false($technique->IsActive, 'inactive');
                Assert::same(0, $technique->AdversaryId, 'no adversary');
            },
        ];
    }
}
