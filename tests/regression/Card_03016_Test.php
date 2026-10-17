<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class Card_03016_Test extends TestCase
{
    public function name(): string
    {
        return '_03016 Schwester Ise';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Academic with two reactions' => function () {
                $ise = new _03016();
                Assert::instanceOf(Character::class, $ise, 'character');
                Assert::instanceOf(IHasReactions::class, $ise, 'reactions');
                Assert::same(5, $ise->Resolve, 'resolve');
                Assert::same(1, $ise->Combat, 'combat');
                Assert::same(2, $ise->Finesse, 'finesse');
                Assert::same(2, $ise->Influence, 'influence');
                Assert::true($ise->hasFaction('Eisen'), 'Eisen');
                Assert::true($ise->hasTrait('Hunter'), 'Hunter');
                Assert::true($ise->hasTrait('Zealot'), 'Zealot');
                Assert::false($ise->WoundedCombatBonusApplied, 'no bonus yet');
                Assert::instanceOf(Reaction_03016a::class, $ise->getReactions()[0], 'stay');
                Assert::instanceOf(Reaction_03016b::class, $ise->getReactions()[1], 'move');
            },

            // WHY: The bonus reads Wounds, which her own wound handler would already
            // have incremented. A second wound does not stack. Healing back to zero removes it.
            // Blanking removes it; unblanking puts it back if she is still wounded.
            'wounded Ise gains +1 Combat, and only once' => function () {
                $world = new TestWorld();
                $ise = $world->placeCharacter(new _03016(), Game::LOCATION_CITY_DOCKS, 1);
                $ise->Wounds = 1;
                $wounded = new EventCharacterWounded();
                $wounded->theah = $world->theah;
                $wounded->characterId = $ise->Id;
                $ise->handleEvent($wounded);
                $combat = $world->theah->queuedOfType(EventCharacterCombatModified::class)[0];
                Assert::same(1, $combat->OldCombat, 'printed');
                Assert::same(2, $combat->NewCombat, 'plus one');
                Assert::true($ise->WoundedCombatBonusApplied, 'flag');

                $world->theah->takeQueuedEvents();
                $ise->handleEvent($wounded);
                Assert::count(0, $world->theah->queuedEvents, 'does not stack');

                $ise->Wounds = 0;
                $healed = new EventCharacterHealed();
                $healed->theah = $world->theah;
                $healed->characterId = $ise->Id;
                $ise->handleEvent($healed);
                $removed = $world->theah->queuedOfType(EventCharacterCombatModified::class)[0];
                Assert::same(0, $removed->NewCombat, 'back to printed relative to current modified');
                Assert::false($ise->WoundedCombatBonusApplied, 'cleared');

                $ise->Wounds = 1;
                $ise->WoundedCombatBonusApplied = true;
                $ise->onAbilitiesBlanked($world->theah);
                Assert::false($ise->WoundedCombatBonusApplied, 'blank removes the bonus');

                $world->theah->takeQueuedEvents();
                $ise->onAbilitiesUnblanked($world->theah);
                Assert::true($ise->WoundedCombatBonusApplied, 'unblank restores it');

                $ise->ControllerId = 0;
                $ise->WoundedCombatBonusApplied = false;
                $ise->Wounds = 1;
                $world->theah->takeQueuedEvents();
                $ise->handleEvent($wounded);
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },
        ];
    }
}
