<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04036a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;

class Action_04036a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04036a';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage cost is the tome, not the bearer. Renown moves to another city
            // location. Home is not a destination. No renown, or an engaged tome, hides it.
            'engages the tome and moves renown to another city location' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $tome = $world->placeCard(new _04036(), Game::LOCATION_CITY_DOCKS, 1);
                $tome->AttachedToId = $host->Id;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04036a $action */
                $action = $tome->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'renown to move');

                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036a, '')['locationIds'];
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $destinations, true), 'not the current location');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $destinations, true), 'city only');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $destinations, true), 'forum');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036a, '', [Game::LOCATION_CITY_FORUM]);
                Assert::same($tome->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'tome engaged');
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class)[0]->toLocation, 'moved');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0]->location, 'removed');
                Assert::true($world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->isMove, 'add is a move');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036a, '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'same location');

                $tome->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged tome');
                $tome->Engaged = false;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no renown');
            },
        ];
    }
}
