<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Montoya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Montoya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02035;

/**
 * WHY: Patricia invents traits only when callers pass $queryCard. Spot-check
 * discounts + ability gates across expansions so the sweep does not regress.
 */
class Patricia_queryCard_Test extends TestCase
{
    public function name(): string
    {
        return 'Patricia_queryCard';
    }

    public function tests(): array
    {
        return [
            'Action_01171 discounts when Patricia performs (Villain|Scoundrel fiction)' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01171 $action */
                $action = $risk->getActions()[0];
                $explanations = [];

                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $patricia, $action, $explanations), 'discount');
            },

            'Action_04030 discounts when Patricia performs (Merchant|Scoundrel fiction)' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04030(), Game::LOCATION_HAND, 1);
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04030 $action */
                $action = $risk->getActions()[0];
                $explanations = [];

                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $patricia, $action, $explanations), 'discount');
            },

            'Action_03071 discounts when Patricia is Leader (Villain|Pirate fiction)' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03071(), Game::LOCATION_HAND, 1);
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $patricia;
                /** @var Action_03071 $action */
                $action = $risk->getActions()[0];
                $explanations = [];

                Assert::same(1, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'leader discount');
            },

            'Action_02035 offers Patricia as Scoundrel performer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02035(), Game::LOCATION_PLAYER_HOME, 1);
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_02035 $action */
                $action = $scheme->getActions()[0];

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($patricia->Id, $ids, true), 'patricia offered');
            },

            'Maneuver_05Montoya available when Patricia is duel actor (Duelist fiction)' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $risk = $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 1);
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $patricia;
                /** @var Maneuver_05Montoya $maneuver */
                $maneuver = $risk->getManeuvers()[0];

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist gate');
            },
        ];
    }
}
