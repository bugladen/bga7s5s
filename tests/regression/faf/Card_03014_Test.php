<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;

class Card_03014_Test extends TestCase
{
    public function name(): string
    {
        return '_03014 Kaspar Dietrich';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Zealot with the Eisenfaust technique' => function () {
                $kaspar = new _03014();
                Assert::instanceOf(Character::class, $kaspar, 'character');
                Assert::same(4, $kaspar->Resolve, 'resolve');
                Assert::same(3, $kaspar->Combat, 'combat');
                Assert::same(1, $kaspar->Finesse, 'finesse');
                Assert::same(2, $kaspar->Influence, 'influence');
                Assert::true($kaspar->hasFaction('Eisen'), 'Eisen');
                Assert::true($kaspar->hasTrait('Zealot'), 'Zealot');
                Assert::true($kaspar->hasTrait('Hero'), 'Hero');
                Assert::false($kaspar->canBeWoundedByOpponentAbilities(), 'opponent abilities cannot wound him');
                Assert::instanceOf(Technique_03014::class, $kaspar->getTechniques()[0], 'technique');
            },

            // WHY: Threat conversion emits the wound with an empty abilityId and still lands.
            // An opponent ability is zeroed. Your own ability, or a source with no controller, is not.
            'opponent abilities cannot wound him, and threat still can' => function () {
                $world = new TestWorld();
                $kaspar = $world->placeCharacter(new _03014(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $threat = new EventCharacterBeingWounded();
                $threat->theah = $world->theah;
                $threat->characterId = $kaspar->Id;
                $threat->sourceId = $foe->Id;
                $threat->wounds = 2;
                $threat->abilityId = '';
                $kaspar->eventCheck($threat);
                Assert::same(2, $threat->wounds, 'threat conversion');

                $ability = new EventCharacterBeingWounded();
                $ability->theah = $world->theah;
                $ability->characterId = $kaspar->Id;
                $ability->sourceId = $foe->Id;
                $ability->wounds = 2;
                $ability->abilityId = 'Technique_x';
                $kaspar->eventCheck($ability);
                Assert::same(0, $ability->wounds, 'opponent ability');

                $own = new EventCharacterBeingWounded();
                $own->theah = $world->theah;
                $own->characterId = $kaspar->Id;
                $own->sourceId = $kaspar->Id;
                $own->wounds = 1;
                $own->abilityId = 'Technique_03013';
                $kaspar->eventCheck($own);
                Assert::same(1, $own->wounds, 'his own ability');

                $loose = $world->placeCharacter(new GenericCharacter('Loose'), Game::LOCATION_CITY_DOCKS, 0);
                $unowned = new EventCharacterBeingWounded();
                $unowned->theah = $world->theah;
                $unowned->characterId = $kaspar->Id;
                $unowned->sourceId = $loose->Id;
                $unowned->wounds = 1;
                $unowned->abilityId = 'something';
                $kaspar->eventCheck($unowned);
                Assert::same(1, $unowned->wounds, 'uncontrolled source');
            },
        ];
    }
}
