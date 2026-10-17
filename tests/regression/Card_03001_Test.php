<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnEnding;

class Card_03001_Test extends TestCase
{
    public function name(): string
    {
        return '_03001 Cesca del Rosso';
    }

    public function tests(): array
    {
        return [
            'constructs the Vodacce Leader with a City Action and a City Reaction' => function () {
                $cesca = new _03001();
                Assert::instanceOf(Leader::class, $cesca, 'Leader');
                Assert::instanceOf(IHasActions::class, $cesca, 'actions');
                Assert::instanceOf(IHasReactions::class, $cesca, 'reactions');
                Assert::same(7, $cesca->Resolve, 'Resolve');
                Assert::same(1, $cesca->Combat, 'Combat');
                Assert::same(2, $cesca->Finesse, 'Finesse');
                Assert::same(4, $cesca->Influence, 'Influence');
                Assert::same(6, $cesca->CrewCap, 'Crew Cap');
                Assert::same(2, $cesca->Panache, 'Panache');
                Assert::true($cesca->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($cesca->hasTrait('Leader'), 'Leader');
                Assert::true($cesca->hasTrait('Strega'), 'Strega');
                Assert::true($cesca->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($cesca->hasTrait('Red Hand'), 'Red Hand');
                Assert::instanceOf(Action_03001::class, $cesca->getActions()[0], 'action');
                Assert::instanceOf(Reaction_03001::class, $cesca->getReactions()[0], 'reaction');
            },

            'end of Dawn draws five cards for her controller' => function () {
                $world = new TestWorld();
                $cesca = $world->placeCharacter(new _03001(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventPhaseDawnEnding();
                $world->fireOn($cesca, $event);

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(5, $draws, 'five draws');
                Assert::same(1, $draws[0]->playerId, 'controller');
            },

            'a locker or uncontrolled Cesca does not draw' => function () {
                $world = new TestWorld();
                $cesca = $world->placeCharacter(new _03001(), Game::LOCATION_PLAYER_HOME, 1);
                $world->game->discardOrLockerByCardId[$cesca->Id] = true;
                $event = new EventPhaseDawnEnding();
                $world->fireOn($cesca, $event);
                Assert::count(0, $world->theah->queuedEvents, 'locker');

                $cesca->ControllerId = 0;
                $world->game->discardOrLockerByCardId[$cesca->Id] = false;
                $world->fireOn($cesca, $event);
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },
        ];
    }
}
