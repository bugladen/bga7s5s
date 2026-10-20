<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;

class Card_01007_Test extends TestCase
{
    public function name(): string
    {
        return '_01007 Aldo Bussotti';
    }

    public function tests(): array
    {
        return [
            'constructs with Action_01007 and printed stats' => function () {
                $aldo = new _01007();
                Assert::instanceOf(IHasActions::class, $aldo, 'has actions');
                Assert::same(4, $aldo->Resolve, 'Resolve');
                Assert::same(1, $aldo->Combat, 'Combat');
                Assert::same(3, $aldo->Finesse, 'Finesse');
                Assert::same(1, $aldo->Influence, 'Influence');
                Assert::true($aldo->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($aldo->hasTrait('Red Hand'), 'Red Hand');
                Assert::instanceOf(Action_01007::class, $aldo->getActions()[0], 'Action_01007');
            },

            'moving home resets influence to printed base' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $aldo->ModifiedInfluence = 4;

                $event = new EventCardMoved();
                $event->cardId = $aldo->Id;
                $event->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($aldo, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence modified queued');
                Assert::same(1, $mods[0]->NewInfluence, 'home influence is printed 1');
            },

            'moving into city sets influence to base + location renown' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 3);

                $event = new EventCardMoved();
                $event->cardId = $aldo->Id;
                $event->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($aldo, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence modified queued');
                Assert::same(4, $mods[0]->NewInfluence, '1 base + 3 renown');
            },

            'renown added at Aldo location updates influence to new total' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                // Hub already applied: location now has 2 after add
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventRenownAddedToLocation();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($aldo, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::same(3, $mods[0]->NewInfluence, '1 + 2 renown after add');
            },

            // WHY regression: audit 2026-04-01 found sign inversion on remove path
            'renown removed at Aldo location uses positive post-hub total (not negated)' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                // Hub already decremented: was 3, removed 1 → now 2
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventRenownRemovedFromLocation();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($aldo, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence event');
                Assert::same(3, $mods[0]->NewInfluence, '1 + 2 — not 1 + (-2)');
            },

            'blanked clears renown-driven influence to base' => function () {
                $world = new TestWorld();
                $aldo = $world->placeCharacter(new _01007(), Game::LOCATION_CITY_DOCKS, 1);
                $aldo->ModifiedInfluence = 5;
                $aldo->onAbilitiesBlanked($world->theah);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::same(1, $mods[0]->NewInfluence, 'blanked resets to printed');
            },

            'state constant registered' => function () {
                Assert::same(401007, States::HIGH_DRAMA_PLAYER_TURN_01007, 'state id');
            },
        ];
    }
}
