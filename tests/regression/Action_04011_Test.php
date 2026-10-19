<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_04011_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04011';
    }

    public function tests(): array
    {
        return [
            // WHY: In play and not available means a controlled Mercenary, including his own.
            // The move home does not engage.
            'sends a controlled mercenary home' => function () {
                $world = new TestWorld();
                $hans = $world->placeCharacter(new _04011(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $available = $world->placeCharacter(new GenericCharacter('Available', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_04011 $action */
                $action = $hans->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'own mercenary counts');
                $merc->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'available mercenary');
                $merc->ControllerId = 2;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'opposing mercenary');
                $hans->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $hans->Location = Game::LOCATION_CITY_DOCKS;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04011, '', $merc->Id);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($available->Id, $available->Id, 'still here');
            },
        ];
    }
}
