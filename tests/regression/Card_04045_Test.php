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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04045_Test extends TestCase
{
    public function name(): string
    {
        return '_04045 Stand Your Ground';
    }

    public function tests(): array
    {
        return [
            'is a relentless challenge scheme' => function () {
                $card = new _04045();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(81, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: Docks or Bazaar only, even at two players. The Forum throws.
            // nextState() is bare.
            'places one renown on the docks or the bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04045(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same('04045', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick');
                $locations = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04045, '', '')['locationIds'];
                Assert::same([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR], $locations, 'docks or bazaar');

                $blocked = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04045, '', '', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'forum');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04045, '', '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'docks');
                Assert::same(null, end($world->game->gamestate->transitions), 'bare nextState');
            },
        ];
    }
}
