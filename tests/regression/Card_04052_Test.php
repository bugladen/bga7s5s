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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04052;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04052_Test extends TestCase
{
    public function name(): string
    {
        return '_04052 Motion to Delay';
    }

    public function tests(): array
    {
        return [
            'is a bureaucracy authority scheme' => function () {
                $card = new _04052();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(90, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($card->hasTrait('Authority'), 'Authority');
            },

            // WHY: First pick is Renown <= 1. Pass throws while one remains. The chosen
            // location cannot be claimed, and eventCheck throws even if a caller skips
            // canLocationBeClaimedBy. High Drama end draws only when an opponent controls
            // more city locations. Locker restores CanBeClaimed.
            'locks a low-renown location and draws when an opponent controls more' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04052(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                Assert::same('04052', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick');

                $must = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04052, '', '');
                } catch (UserException) {
                    $must = true;
                }
                Assert::true($must, 'a low-renown location exists');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 5);
                $rich = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04052, '', '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $rich = true;
                }
                Assert::true($rich, 'more than one renown');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04052, '', '', [Game::LOCATION_CITY_FORUM]);
                Assert::same(Game::LOCATION_CITY_FORUM, $scheme->ChosenLocation, 'chosen');
                Assert::false($world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->CanBeClaimed, 'cannot be controlled');

                $claim = new EventLocationClaimed();
                $claim->location = Game::LOCATION_CITY_FORUM;
                $claim->theah = $world->theah;
                $blocked = false;
                try {
                    $scheme->eventCheck($claim);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'claim of the chosen location');

                $claim->location = Game::LOCATION_CITY_DOCKS;
                $scheme->eventCheck($claim);

                $same = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04052_2, '', '', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException) {
                    $same = true;
                }
                Assert::true($same, 'renown must be somewhere else');

                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04052_2, '', '', [Game::LOCATION_CITY_BAZAAR]);
                Assert::same(Game::LOCATION_CITY_BAZAAR, $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0]->location, 'other location');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 2);
                $world->fireOn($scheme, new EventHighDramaPhaseEnd());
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'tied counts');

                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                $world->fireOn($scheme, new EventHighDramaPhaseEnd());
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'opponent controls more');

                $locker = new EventCardSentToLocker();
                $locker->cardId = $scheme->Id;
                $world->fireOn($scheme, $locker);
                Assert::same('', $scheme->ChosenLocation, 'cleared');
                Assert::true($world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->CanBeClaimed, 'restored');
            },
        ];
    }
}
