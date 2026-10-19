<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03049_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03049';
    }

    public function tests(): array
    {
        return [
            // WHY: Any claim at her location, including an opponent's. This is a continuous
            // reaction, so a successful move does not mark it used. The move does not engage.
            'she may move after her location is claimed, and stay available' => function () {
                $world = new TestWorld();
                $ekaterina = $world->placeCharacter(new _03049(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03049 $reaction */
                $reaction = $ekaterina->getReactions()[0];
                $claimed = new EventLocationClaimed();
                $claimed->theah = $world->theah;
                $claimed->playerId = 2;
                $claimed->location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($claimed);
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('moveTo-' . Game::LOCATION_CITY_FORUM, $buttons, true), 'forum');
                Assert::false(in_array('moveTo-' . Game::LOCATION_CITY_DOCKS, $buttons, true), 'not here');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'stays');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . Game::LOCATION_CITY_FORUM);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::false($reaction->Used, 'continuous');
            },

            'another location, Home, and the locker do not offer a move' => function () {
                $world = new TestWorld();
                $ekaterina = $world->placeCharacter(new _03049(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03049 $reaction */
                $reaction = $ekaterina->getReactions()[0];
                $claimed = new EventLocationClaimed();
                $claimed->theah = $world->theah;
                $claimed->playerId = 1;
                $claimed->location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($claimed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'different location');

                $ekaterina->Location = Game::LOCATION_PLAYER_HOME;
                $claimed->location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($claimed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in the city');

                $ekaterina->Location = Game::LOCATION_CITY_DOCKS;
                $claimed->location = Game::LOCATION_CITY_DOCKS;
                $world->game->discardOrLockerByCardId[$ekaterina->Id] = true;
                $reaction->handleEvent($claimed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'locker');
            },
        ];
    }
}
