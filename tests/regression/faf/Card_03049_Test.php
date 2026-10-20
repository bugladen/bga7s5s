<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;

class Card_03049_Test extends TestCase
{
    public function name(): string
    {
        return '_03049 Ekaterina Ilyanava';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran leader with a reaction and a technique' => function () {
                $ekaterina = new _03049();
                Assert::instanceOf(Leader::class, $ekaterina, 'leader');
                Assert::instanceOf(IHasReactions::class, $ekaterina, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $ekaterina, 'techniques');
                Assert::same(6, $ekaterina->Resolve, 'resolve');
                Assert::same(1, $ekaterina->Combat, 'combat');
                Assert::same(2, $ekaterina->Finesse, 'finesse');
                Assert::same(3, $ekaterina->Influence, 'influence');
                Assert::same(5, $ekaterina->CrewCap, 'crew');
                Assert::same(6, $ekaterina->Panache, 'panache');
                Assert::true($ekaterina->hasFaction('Ussura'), 'Ussura');
                Assert::true($ekaterina->hasTrait('Leader'), 'Leader');
                Assert::true($ekaterina->hasTrait('Academic'), 'Academic');
                Assert::true($ekaterina->hasTrait('Explorer'), 'Explorer');
                Assert::instanceOf(Reaction_03049::class, $ekaterina->getReactions()[0], 'reaction');
                Assert::instanceOf(Technique_03049::class, $ekaterina->getTechniques()[0], 'technique');
            },

            // WHY: Plunder is Take then Gains then Removed, and all three shrink by one.
            // A direct Collect is Removed then Gains. The put-back is queued from handleEvent
            // so it lands after the Removed. Any event between Removed and Gains drops the arm.
            'an opponent collects one fewer Renown from her location' => function () {
                $world = new TestWorld();
                $ekaterina = $world->placeCharacter(new _03049(), Game::LOCATION_CITY_DOCKS, 1);

                $take = new EventPlayerTakeReknownForControlledLocation();
                $take->theah = $world->theah;
                $take->playerId = 2;
                $take->location = Game::LOCATION_CITY_DOCKS;
                $take->reknown = 3;
                $ekaterina->eventCheck($take);
                Assert::same(2, $take->reknown, 'plunder take');

                $gains = new EventPlayerGainsReknown();
                $gains->theah = $world->theah;
                $gains->playerId = 2;
                $gains->amount = 3;
                $ekaterina->eventCheck($gains);
                Assert::same(2, $gains->amount, 'plunder gains');

                $removed = new EventRenownRemovedFromLocation();
                $removed->theah = $world->theah;
                $removed->playerId = 0;
                $removed->location = Game::LOCATION_CITY_DOCKS;
                $removed->amount = 3;
                $ekaterina->eventCheck($removed);
                Assert::same(2, $removed->amount, 'plunder removed');

                $collectRemove = new EventRenownRemovedFromLocation();
                $collectRemove->theah = $world->theah;
                $collectRemove->playerId = 2;
                $collectRemove->location = Game::LOCATION_CITY_DOCKS;
                $collectRemove->amount = 2;
                $ekaterina->eventCheck($collectRemove);
                Assert::same(2, $collectRemove->amount, 'collect does not shrink the remove yet');

                $collectGain = new EventPlayerGainsReknown();
                $collectGain->theah = $world->theah;
                $collectGain->playerId = 2;
                $collectGain->amount = 2;
                $ekaterina->eventCheck($collectGain);
                Assert::same(1, $collectGain->amount, 'collect gains one fewer');
                $ekaterina->handleEvent($collectGain);
                $putBack = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $putBack->location, 'remaining stays');
                Assert::same(1, $putBack->amount, 'one put back');
            },

            'her own plunder, a gap before the gain, and being out of the city do nothing' => function () {
                $world = new TestWorld();
                $ekaterina = $world->placeCharacter(new _03049(), Game::LOCATION_CITY_DOCKS, 1);
                $own = new EventPlayerTakeReknownForControlledLocation();
                $own->theah = $world->theah;
                $own->playerId = 1;
                $own->location = Game::LOCATION_CITY_DOCKS;
                $own->reknown = 3;
                $ekaterina->eventCheck($own);
                Assert::same(3, $own->reknown, 'her own plunder');

                $removed = new EventRenownRemovedFromLocation();
                $removed->theah = $world->theah;
                $removed->playerId = 2;
                $removed->location = Game::LOCATION_CITY_DOCKS;
                $removed->amount = 2;
                $ekaterina->eventCheck($removed);
                $gap = new EventCardDrawn();
                $gap->theah = $world->theah;
                $ekaterina->eventCheck($gap);
                $gains = new EventPlayerGainsReknown();
                $gains->theah = $world->theah;
                $gains->playerId = 2;
                $gains->amount = 2;
                $ekaterina->eventCheck($gains);
                Assert::same(2, $gains->amount, 'the arm did not survive the gap');

                $ekaterina->Location = Game::LOCATION_PLAYER_HOME;
                $away = new EventPlayerTakeReknownForControlledLocation();
                $away->theah = $world->theah;
                $away->playerId = 2;
                $away->location = Game::LOCATION_PLAYER_HOME;
                $away->reknown = 3;
                $ekaterina->eventCheck($away);
                Assert::same(3, $away->reknown, 'not in the city');

                $ekaterina->Location = Game::LOCATION_CITY_DOCKS;
                $world->game->discardOrLockerByCardId[$ekaterina->Id] = true;
                $locker = new EventPlayerTakeReknownForControlledLocation();
                $locker->theah = $world->theah;
                $locker->playerId = 2;
                $locker->location = Game::LOCATION_CITY_DOCKS;
                $locker->reknown = 3;
                $ekaterina->eventCheck($locker);
                Assert::same(3, $locker->reknown, 'in the locker');
            },
        ];
    }
}
