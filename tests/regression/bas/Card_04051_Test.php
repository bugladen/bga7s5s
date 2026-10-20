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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04051_Test extends TestCase
{
    public function name(): string
    {
        return '_04051 A Shared Interest';
    }

    public function tests(): array
    {
        return [
            'is a bargain savvy scheme' => function () {
                $card = new _04051();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(31, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Bargain'), 'Bargain');
                Assert::true($card->hasTrait('Savvy'), 'Savvy');
            },

            // WHY: The chosen opponent is stored on the card until Planning End, not in
            // CHOSEN_OPPONENT. Their renown and yours must be different locations.
            // Planning End claims, then the opponent claims a different city location.
            // If they have no other claimable location, the second pick is skipped.
            // Locker clears the stored opponent.
            'opponent places renown, then both claim at planning end' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04051(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same('04051', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choose opponent');

                $self = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051, '', '', 1);
                } catch (UserException) {
                    $self = true;
                }
                Assert::true($self, 'cannot choose yourself');

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051, '', '', 2);
                Assert::same(2, $scheme->chosenOpponentId, 'stored on the card');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[1]->playerId, 'opponent places');

                $world->game->activePlayerId = 1;
                $wrong = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_2, '', '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $wrong = true;
                }
                Assert::true($wrong, 'not the chosen player');

                $world->game->activePlayerId = 2;
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_2, '', '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $scheme->firstRenownLocation, 'first site');
                Assert::same(2, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->playerId, 'opponent renown');

                $same = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_3, '', '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $same = true;
                }
                Assert::true($same, 'must be different');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04051_3, '', '', [Game::LOCATION_CITY_FORUM]);
                Assert::same('', $scheme->firstRenownLocation, 'cleared after the second renown');
                Assert::same(1, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[1]->playerId, 'your renown');
                Assert::same(2, $scheme->chosenOpponentId, 'opponent survives until planning end');

                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, new EventPhasePlanningEnd());
                Assert::same('04051', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'you claim first');

                foreach ([
                    Game::LOCATION_CITY_FORUM,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_OLES_INN,
                    Game::LOCATION_CITY_GOVERNORS_GARDEN,
                ] as $name) {
                    $world->theah->setLocationCanBeClaimed($name, false);
                }
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_END_04051, '', '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'your claim');
                Assert::same(0, $scheme->chosenOpponentId, 'opponent had nowhere else');

                $scheme->chosenOpponentId = 2;
                $scheme->IsUpdated = true;
                foreach ($world->theah->getCityLocations() as $location) {
                    $world->theah->setLocationCanBeClaimed($location->Name, true);
                }
                $world->theah->takeQueuedEvents();
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_END_04051, '', '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same('04051_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'opponent claims');
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_END_04051_2, '', '', [Game::LOCATION_CITY_FORUM]);
                Assert::same(2, $world->theah->queuedOfType(EventLocationClaimed::class)[1]->playerId, 'their claim');
                Assert::same(0, $scheme->chosenOpponentId, 'finished');

                $scheme->chosenOpponentId = 2;
                $locker = new EventCardSentToLocker();
                $locker->cardId = $scheme->Id;
                $world->fireOn($scheme, $locker);
                Assert::same(0, $scheme->chosenOpponentId, 'locker clears');
            },
        ];
    }
}
