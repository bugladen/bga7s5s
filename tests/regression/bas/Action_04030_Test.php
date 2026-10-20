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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_04030_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04030';
    }

    public function tests(): array
    {
        return [
            // WHY: City action moves to an adjacent city location with strictly more
            // Renown. Two players: Docks touches Forum, not the Bazaar. Merchant or
            // Scoundrel is -1 on this action only. Move does not engage.
            'moves to the richer adjacent location and discounts a merchant' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $risk = $world->placeCard(new _04030(), Game::LOCATION_HAND, 1);
                $scoundrel = $world->placeCharacter(new _04022(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 5);
                /** @var Action_04030 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $scoundrel->Id);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'forum is richer and adjacent');
                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04030, '')['locationIds'];
                Assert::same([Game::LOCATION_CITY_FORUM], $destinations, 'bazaar is not adjacent');

                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $scoundrel, $action, $explanations), 'scoundrel discount');
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $hero, $action, $explanations), 'hero pays full');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04030, '', [Game::LOCATION_CITY_FORUM]);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'move only');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04030, '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bazaar');

                $scoundrel->Location = Game::LOCATION_PLAYER_HOME;
                $hero->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home has no city adjacency');
            },
        ];
    }
}
