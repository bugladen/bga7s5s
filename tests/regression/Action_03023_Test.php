<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;

class Action_03023_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03023';
    }

    public function tests(): array
    {
        return [
            'is a city action that must pick a performer' => function () {
                $action = new Action_03023();
                Assert::instanceOf(RiskCityAction::class, $action, 'city action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Two wounds, in the city. One wound is not enough. Home does not count.
            // Engage is not a cost. The trigger heals one and resolves the action.
            'a performer with two wounds heals one' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03023(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Wounds = 2;
                $performer->Engaged = true;
                /** @var Action_03023 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'two wounds, engaged');
                Assert::same($performer->Id, $action->getPerformersForAction(1, $world->theah)[0]->Id, 'performer');

                $performer->Wounds = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'one wound');
                $performer->Wounds = 2;
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                $heal = $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0];
                Assert::same($performer->Id, $heal->characterId, 'performer');
                Assert::same(1, $heal->wounds, 'one');
                Assert::same($risk->Id, $heal->sourceId, 'risk');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
