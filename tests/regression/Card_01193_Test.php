<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01193;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01193;

class Card_01193_Test extends TestCase
{
    public function name(): string
    {
        return '_01193 Burnished Cuirass';
    }

    public function tests(): array
    {
        return [
            // WHY: CityAttachment Armor — Resolve +1; no FactionAttachment Riposte pin.
            'constructs CityAttachment Armor with Technique_01193' => function () {
                $cuirass = new _01193();
                Assert::instanceOf(CityAttachment::class, $cuirass, 'CityAttachment');
                Assert::instanceOf(IHasTechniques::class, $cuirass, 'techniques');
                Assert::same(1, $cuirass->WealthCost, 'wealth');
                Assert::same(17, $cuirass->CityCardNumber, 'city #');
                Assert::same(1, $cuirass->ResolveModifier, 'Resolve +1');
                Assert::same(0, $cuirass->CombatModifier, 'combat');
                Assert::true($cuirass->hasTrait('Armor'), 'Armor');
                Assert::instanceOf(Technique_01193::class, $cuirass->getTechniques()[0], 'Technique');
            },

            'owns Technique_01193 when placed' => function () {
                $world = new TestWorld();
                $cuirass = $world->placeCard(new _01193(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $cuirass->getTechniques(), 'one');
                Assert::same($cuirass->Id, $cuirass->getTechniques()[0]->OwnerId, 'owner');
            },
        ];
    }
}
