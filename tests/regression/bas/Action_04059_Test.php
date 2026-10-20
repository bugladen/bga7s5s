<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04059;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04059_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04059';
    }

    public function tests(): array
    {
        return [
            // WHY: The -1 is only this action, and only for a Merchant or Scoundrel.
            // The action itself wants an en garde non-Hero in the city and an available
            // Mercenary. It opens the parley recruit flow and does not resolve here.
            'recruits an available mercenary and discounts merchants' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04059(), Game::LOCATION_HAND, 1);
                $scoundrel = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero', 'Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 0);
                $hired = $world->placeCharacter(new GenericCharacter('Hired', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04059 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available mercenary');
                $ids = array_map(fn($character) => $character->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$scoundrel->Id], array_values($ids), 'hero hidden');

                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $scoundrel, $action, $explanations), 'scoundrel');
                $other = $world->placeCard(new _04057(), Game::LOCATION_HAND, 1);
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $scoundrel, $other->getActions()[0], $explanations), 'another action');
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'no performer');
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_FORUM, 1);
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $plain, $action, $explanations), 'no trait');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $scoundrel->Id);
                [$ok] = $action->isValidTargetForAbility($world->game, $merc);
                Assert::true($ok, 'uncontrolled mercenary');
                [$hiredOk] = $action->isValidTargetForAbility($world->game, $hired);
                Assert::false($hiredOk, 'already controlled');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same(Game::SILVER_TONGUE_RECRUIT_TYPE, $world->game->globals->get(Game::RECRUIT_TYPE), 'parley recruit');
                Assert::same('04059', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'opens');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'merges into recruit');

                $scoundrel->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $scoundrel->Engaged = false;
                $merc->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no available mercenary');
                Assert::false($hero->Id === $scoundrel->Id, 'distinct');
            },
        ];
    }
}
