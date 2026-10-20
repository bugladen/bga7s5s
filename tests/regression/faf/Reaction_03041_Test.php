<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03041;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03041_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03041';
    }

    public function tests(): array
    {
        return [
            // WHY: The claimed location is remembered at offer time. Pass forgets it.
            // A move is one batch: moving, removed, and added-as-move.
            'moves a Renown off the location you just claimed' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_03041 $reaction */
                $reaction = $scheme->getReactions()[0];
                $claimed = new EventLocationClaimed();
                $claimed->theah = $world->theah;
                $claimed->playerId = 1;
                $claimed->location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($claimed);
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('moveTo-' . Game::LOCATION_CITY_FORUM, $buttons, true), 'forum');
                Assert::false(in_array('moveTo-' . Game::LOCATION_CITY_DOCKS, $buttons, true), 'not the source');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');

                $reaction->handleEvent($claimed);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . Game::LOCATION_CITY_FORUM);
                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class)[0];
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0];
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving->toLocation, 'to forum');
                Assert::same($moving->batchId, $removed->batchId, 'same batch');
                Assert::same($moving->batchId, $added->batchId, 'added in the batch');
                Assert::true($added->isMove, 'this add is a move');
                Assert::true($reaction->Used, 'spent');
            },

            'an opponent\'s claim, or a location with no Renown, does not offer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03041(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_03041 $reaction */
                $reaction = $scheme->getReactions()[0];
                $claimed = new EventLocationClaimed();
                $claimed->theah = $world->theah;
                $claimed->playerId = 2;
                $claimed->location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($claimed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not your claim');

                $claimed->playerId = 1;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                $reaction->handleEvent($claimed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no renown');
            },
        ];
    }
}
