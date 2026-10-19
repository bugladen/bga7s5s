<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04034;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04034_Test extends TestCase
{
    public function name(): string
    {
        return '_04034 Explosive Ultimatum';
    }

    public function tests(): array
    {
        return [
            'is a cunning sabotage scheme' => function () {
                $card = new _04034();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(28, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Sabotage'), 'Sabotage');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: Move-instead requires unique fewest Renown and a Renown that exists.
            // A tie is not fewest. Pass without that option throws. The move must be adjacent.
            'places renown, or moves it when you uniquely have the fewest' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04034(), Game::LOCATION_PLAYER_HOME, 1);
                Assert::false($scheme->playerHasFewestRenown($world->game, 1), 'tie at zero');
                $world->game->setPlayerReknown(2, 3);
                Assert::true($scheme->playerHasFewestRenown($world->game, 1), 'unique fewest');

                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Player One';
                $world->fireOn($scheme, $resolve);
                Assert::same('04034', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');
                Assert::false($scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034, '', '')['canMoveRenown'], 'nothing to move yet');

                $threw = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034, '', '');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must place when nothing can move');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034, '', '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'placed');
                Assert::same('renownPlaced', end($world->game->gamestate->transitions), 'placed transition');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034, '', '')['canMoveRenown'], 'may move');
                $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034, '', '');
                Assert::same('pass', end($world->game->gamestate->transitions), 'move path');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_2, '', '', [Game::LOCATION_CITY_DOCKS]);
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_3, '', '', [Game::LOCATION_CITY_FORUM]);
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class)[0]->toLocation, 'adjacent');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0]->location, 'removed');
                Assert::true($world->theah->queuedOfType(EventRenownAddedToLocation::class)[1]->isMove, 'add is a move');

                $blocked = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04034_3, '', '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'bazaar is not adjacent to the docks');
            },
        ];
    }
}
