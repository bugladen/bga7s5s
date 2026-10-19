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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04014_Test extends TestCase
{
    public function name(): string
    {
        return '_04014 Forged for Battle';
    }

    public function tests(): array
    {
        return [
            'is a prepared scheme' => function () {
                $card = new _04014();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(45, $card->Initiative, 'initiative');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Docks renown is fixed. The second renown is any other city location,
            // and the pick is medium priority so the docks renown resolves first.
            'resolve puts renown on the docks and asks for one other location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04014(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'docks');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04014', $transition->transition, 'pick');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'after the docks renown');

                $args = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04014, '', '');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'docks excluded');
                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_04014,
                    '',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[1]->location, 'second');
                Assert::same(null, $world->game->gamestate->transitions[0], 'bare next state');

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_04014,
                        '',
                        '',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'docks is not the second location');
            },
        ];
    }
}
