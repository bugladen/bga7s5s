<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03058_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03058';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03058();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Duelist only. Their controller must have strictly more characters here.
            // No Engage cost, so an engaged Duelist stays legal. The type stays off auto-engage.
            // The trigger does not resolve the action.
            'a duelist can challenge when outnumbered' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03058(), Game::LOCATION_HAND, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03058 $action */
                $action = $risk->getActions()[0];

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'tied');
                $second = $world->placeCharacter(new GenericCharacter('Second'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'outnumbered');

                $performerIds = array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$duelist->Id], $performerIds, 'duelist only');

                $duelist->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged duelist');
                $duelist->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                [$valid] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($valid, 'foe');
                $second->ControllerId = 0;
                [$valid] = $action->isValidTargetForAbility($world->game, $second);
                Assert::false($valid, 'uncontrolled');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(Game::COURAGEOUS_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'courageous');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('03058', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
            },
        ];
    }
}
