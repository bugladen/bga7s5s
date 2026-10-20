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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04025;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04025_Test extends TestCase
{
    public function name(): string
    {
        return '_04025 No Rest for the Wicked';
    }

    public function tests(): array
    {
        return [
            'is a trade fortune scheme' => function () {
                $card = new _04025();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(96, $card->Initiative, 'initiative');
                Assert::same(-2, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Trade'), 'Trade');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: The Bazaar city card is skipped when the city deck is empty, but the
            // Renown pick still opens. That Renown cannot go to the Bazaar.
            'resolve always opens the other-location renown pick' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04025(), Game::LOCATION_PLAYER_HOME, 1);
                $city = $world->placeCard(new _04025(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $city->Id]];
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same($city->Id, $world->theah->queuedOfType(EventCityCardAddedToLocation::class)[0]->cardId, 'bazaar card');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $world->theah->queuedOfType(EventCityCardAddedToLocation::class)[0]->location, 'bazaar');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04025', $transition->transition, 'pick');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'after the city card');

                $names = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04025, '', '')['locationIds'];
                Assert::false(in_array(Game::LOCATION_CITY_BAZAAR, $names, true), 'bazaar excluded');
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04025, '', '', [Game::LOCATION_CITY_FORUM]);
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'forum');
                Assert::same(null, end($world->game->gamestate->transitions), 'sole transition');

                $threw = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04025, '', '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not the bazaar');

                $world->game->topCityCards = [];
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'empty city deck');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'renown still offered');
            },
        ];
    }
}
