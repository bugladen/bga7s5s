<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03067;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03067_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03067';
    }

    public function tests(): array
    {
        return [
            'is a risk city action' => function () {
                $action = new Action_03067();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::false($action->RequiresPerformerSelected, 'leader is implicit');
            },

            // WHY: The Leader is the only performer, and only while you control fewer locations
            // than an opponent. Success claims. The risk goes to the locker either way.
            'wounds the leader, pressures, and lockers the risk' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03067(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                /** @var Action_03067 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'tied at zero');

                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'they have more');
                $leader->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader home');
                $leader->Location = Game::LOCATION_CITY_DOCKS;

                $leader->DashedCombat = true;
                $leader->DashedFinesse = true;
                $leader->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no stat');
                $leader->DashedCombat = false;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same($leader->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                Assert::same($leader->Id, (int) $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same('03067', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'stat');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03067, 'x', 2);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'finesse dashed');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03067, 'x', 1);
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::PRESSURE_STAT), 'combat');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'pressure');
                Assert::same(['statChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');

                $result = new EventLocationPressureResult();
                $result->theah = $world->theah;
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->playerId = 1;
                $result->performerId = $leader->Id;
                $result->location = Game::LOCATION_CITY_DOCKS;
                $action->handleEvent($result);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'locker');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $world->theah->queuedEvents = [];
                $result->success = true;
                $action->handleEvent($result);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'claimed');
                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'still lockered');
            },
        ];
    }
}
