<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_04028_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04028';
    }

    public function tests(): array
    {
        return [
            // WHY: Destinations are city locations you claim-control, or where your Leader
            // is, excluding the current location. Control is the Control_ global, not the
            // CityLocation property. Both moves are engage false. The target click waits.
            'moves the target and the musketeer without engaging' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04028(), Game::LOCATION_HAND, 1);
                $musketeer = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $leader = $world->placeCharacter(new GenericLeader('Mine'), Game::LOCATION_CITY_BAZAAR, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_04028 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $musketeer->Id);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');
                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04028_2, '')['locationIds'];
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $destinations, true), 'controlled forum');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $destinations, true), 'leader bazaar');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $destinations, true), 'not the current location');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04028, '', $foe->Id);
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'target click waits');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04028_2, '', [Game::LOCATION_CITY_FORUM]);
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($foe->Id, $moves[0]->cardId, 'target first');
                Assert::same($musketeer->Id, $moves[1]->cardId, 'performer second');
                Assert::false($moves[0]->engage, 'target not engaged');
                Assert::false($moves[1]->engage, 'performer not engaged');
                Assert::same($moves[0]->batchId, $moves[1]->batchId, 'same batch');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04028_2, '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'current location');

                $musketeer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged musketeer');
            },
        ];
    }
}
