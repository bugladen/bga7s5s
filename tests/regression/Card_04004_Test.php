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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04004_Test extends TestCase
{
    public function name(): string
    {
        return '_04004 Blood Money';
    }

    public function tests(): array
    {
        return [
            'is an assassination scheme' => function () {
                $card = new _04004();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(8, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Assassination'), 'Assassination');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Renown always lands on the Docks and the Bazaar. The Duelist move is
            // contingent: no legal Duelist means no pick state. Move does not engage.
            'resolve adds both renown and only then offers a duelist move' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04004(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $renown[1]->location, 'bazaar');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no duelist');

                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04004', $transition->transition, 'pick');
                Assert::same(Event::MEDIUM_PRIORITY, $transition->priority, 'after renown');

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04004, '', '', $duelist->Id);
                Assert::same($duelist->Id, (int) $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_04004_2,
                    '',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'move only');

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_04004_2,
                        '',
                        '',
                        [Game::LOCATION_PLAYER_HOME]
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'home is not a city destination');
            },
        ];
    }
}
