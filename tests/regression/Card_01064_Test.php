<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;

class Card_01064_Test extends TestCase
{
    public function name(): string
    {
        return '_01064 Guillen de Murrieta';
    }

    private function setRenown(TestWorld $world, int $mine, int $theirs): void
    {
        $world->game->setPlayerReknown(1, $mine);
        $world->game->setPlayerReknown(2, $theirs);
    }

    private function gains(TestWorld $world): EventPlayerGainsReknown
    {
        $event = new EventPlayerGainsReknown();
        $event->playerId = 2;
        $event->amount = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Diplomat Merchant with Action_01064' => function () {
                $guillen = new _01064();
                Assert::instanceOf(IHasActions::class, $guillen, 'actions');
                Assert::same(4, $guillen->Resolve, 'Resolve');
                Assert::same(2, $guillen->Combat, 'Combat');
                Assert::same(1, $guillen->Finesse, 'Finesse');
                Assert::same(2, $guillen->Influence, 'Influence');
                Assert::true($guillen->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($guillen->hasTrait('Merchant'), 'Merchant');
                Assert::true($guillen->hasTrait('Castille'), 'Castille');
                Assert::instanceOf(Action_01064::class, $guillen->getActions()[0], 'Action_01064');
            },

            'gains +1 Combat when an opponent gains more Renown than you' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 1, 3);

                $world->fireOn($guillen, $this->gains($world));

                $mods = $world->theah->queuedOfType(EventCharacterCombatModified::class);
                Assert::count(1, $mods, 'combat modified');
                Assert::same(2, $mods[0]->OldCombat, 'old');
                Assert::same(3, $mods[0]->NewCombat, 'new');
                Assert::same(3, $guillen->ModifiedCombat, 'ModifiedCombat');
            },

            'no bonus when Renown is tied or you are ahead' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);

                $this->setRenown($world, 2, 2);
                $world->fireOn($guillen, $this->gains($world));
                $this->setRenown($world, 5, 2);
                $world->fireOn($guillen, $this->gains($world));

                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'no change');
                Assert::same(2, $guillen->ModifiedCombat, 'printed Combat');
            },

            'bonus is applied once while the opponent stays ahead' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 1, 3);

                $world->fireOn($guillen, $this->gains($world));
                $world->fireOn($guillen, $this->gains($world));

                Assert::count(1, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'once');
                Assert::same(3, $guillen->ModifiedCombat, 'single +1');
            },

            'bonus drops when you catch up after losing Renown for the opponent' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 1, 3);
                $world->fireOn($guillen, $this->gains($world));

                $this->setRenown($world, 3, 3);
                $lost = new EventPlayerLosesReknown();
                $lost->playerId = 2;
                $lost->amount = 1;
                $lost->theah = $world->theah;
                $world->fireOn($guillen, $lost);

                Assert::same(2, $guillen->ModifiedCombat, 'bonus removed');
                Assert::count(2, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'gain + loss');
            },

            'checks Renown when Guillen is mustered or played from the Approach deck' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 0, 4);

                $mustered = new EventCharacterMustered();
                $mustered->characterId = $guillen->Id;
                $mustered->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($guillen, $mustered);
                Assert::same(3, $guillen->ModifiedCombat, 'muster');

                $world2 = new TestWorld();
                $guillen2 = $world2->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world2, 0, 4);
                $played = new EventApproachCharacterPlayed();
                $played->characterId = $guillen2->Id;
                $world2->fireOn($guillen2, $played);
                Assert::same(3, $guillen2->ModifiedCombat, 'approach');
            },

            'muster of another character does not evaluate the bonus' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 0, 4);

                $mustered = new EventCharacterMustered();
                $mustered->characterId = $guillen->Id + 99;
                $mustered->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($guillen, $mustered);

                Assert::same(2, $guillen->ModifiedCombat, 'untouched');
            },

            // WHY: Fate's Silence skips handleEvent, so the blank hook must remove a stuck +1 Combat.
            'blanking removes the bonus and unblanking re-evaluates it' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 1, 3);
                $world->fireOn($guillen, $this->gains($world));
                Assert::same(3, $guillen->ModifiedCombat, 'precondition');

                $guillen->onAbilitiesBlanked($world->theah);
                Assert::same(2, $guillen->ModifiedCombat, 'removed on blank');

                $guillen->onAbilitiesUnblanked($world->theah);
                Assert::same(3, $guillen->ModifiedCombat, 're-applied on unblank');
            },

            'blanking without the bonus changes nothing' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 3, 1);

                $guillen->onAbilitiesBlanked($world->theah);

                Assert::same(2, $guillen->ModifiedCombat, 'no drop below printed');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'no event');
            },

            'blanked Guillen ignores Renown events' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $guillen->addCondition(Game::FATES_SILENCE_CONDITION);
                $this->setRenown($world, 1, 3);

                $world->fireOn($guillen, $this->gains($world));

                Assert::same(2, $guillen->ModifiedCombat, 'blanked');
            },

            'unblank does nothing when dying or in discard/locker' => function () {
                $world = new TestWorld();
                $guillen = $world->placeCharacter(new _01064(), Game::LOCATION_CITY_DOCKS, 1);
                $this->setRenown($world, 0, 4);

                $guillen->IsDying = true;
                $guillen->onAbilitiesUnblanked($world->theah);
                Assert::same(2, $guillen->ModifiedCombat, 'dying');

                $guillen->IsDying = false;
                $world->game->forceInDiscardOrLocker = true;
                $guillen->onAbilitiesUnblanked($world->theah);
                Assert::same(2, $guillen->ModifiedCombat, 'discard/locker');
            },
        ];
    }
}
