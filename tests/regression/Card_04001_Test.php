<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class Card_04001_Test extends TestCase
{
    public function name(): string
    {
        return '_04001 Benci Bommarito';
    }

    public function tests(): array
    {
        return [
            'is a Vodacce duelist with one technique' => function () {
                $card = new _04001();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(1, $card->CardNumber, 'number');
                Assert::same(2, $card->Combat, 'combat');
                Assert::true($card->hasTrait('Duelist'), 'Duelist');
                Assert::true($card->hasTrait('Red Hand'), 'Red Hand');
                Assert::count(1, $card->getTechniques(), 'technique');
                Assert::false($card->OpposedWoundedCombatBonusApplied, 'flag starts clear');
            },

            // WHY: ±1 flag, not a stored absolute. EventCardMoved is before location
            // updates, so a leaver still listed here is excluded and an arriver is added.
            // Home is a shared string and never counts as opposed.
            'gains combat only while two wounded opponents share his city location' => function () {
                $world = new TestWorld();
                $benci = $world->placeCharacter(new _04001(), Game::LOCATION_CITY_DOCKS, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 2);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);
                $a->Wounds = 1;
                $b->Wounds = 1;

                $arrive = new EventCardMoved();
                $arrive->cardId = $benci->Id;
                $arrive->fromLocation = Game::LOCATION_CITY_FORUM;
                $arrive->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($benci, $arrive);

                Assert::true($benci->OpposedWoundedCombatBonusApplied, 'applied');
                $up = $world->theah->queuedOfType(EventCharacterCombatModified::class)[0];
                Assert::same(2, $up->OldCombat, 'from printed');
                Assert::same(3, $up->NewCombat, 'plus one');

                $world->theah->takeQueuedEvents();
                $leave = new EventCardMoved();
                $leave->cardId = $b->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($benci, $leave);
                Assert::false($benci->OpposedWoundedCombatBonusApplied, 'one left');
                $down = $world->theah->queuedOfType(EventCharacterCombatModified::class)[0];
                Assert::same(1, $down->NewCombat, 'minus one from unmodified');

                $world->theah->takeQueuedEvents();
                $benci->Location = Game::LOCATION_PLAYER_HOME;
                $a->Location = Game::LOCATION_PLAYER_HOME;
                $b->Location = Game::LOCATION_PLAYER_HOME;
                $home = new EventCardMoved();
                $home->cardId = $benci->Id;
                $home->fromLocation = Game::LOCATION_CITY_DOCKS;
                $home->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($benci, $home);
                Assert::false($benci->OpposedWoundedCombatBonusApplied, 'home');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'no home bonus');
            },

            // WHY: Wounded/Healed can reach Benci before the victim's Wounds update.
            // characterHandled false applies the event delta. Controller 0 never stamps.
            'a not-yet-applied wound counts, and an uncontrolled Benci does not' => function () {
                $world = new TestWorld();
                $benci = $world->placeCharacter(new _04001(), Game::LOCATION_CITY_DOCKS, 1);
                $already = $world->placeCharacter(new GenericCharacter('Already'), Game::LOCATION_CITY_DOCKS, 2);
                $fresh = $world->placeCharacter(new GenericCharacter('Fresh'), Game::LOCATION_CITY_DOCKS, 2);
                $already->Wounds = 1;

                $wound = new EventCharacterWounded();
                $wound->characterId = $fresh->Id;
                $wound->wounds = 1;
                $wound->characterHandled = false;
                $world->fireOn($benci, $wound);
                Assert::true($benci->OpposedWoundedCombatBonusApplied, 'stale wounds still count');

                $benci->ControllerId = 0;
                $benci->OpposedWoundedCombatBonusApplied = false;
                $world->theah->takeQueuedEvents();
                $world->fireOn($benci, $wound);
                Assert::false($benci->OpposedWoundedCombatBonusApplied, 'uncontrolled');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'no event');
            },
        ];
    }
}
