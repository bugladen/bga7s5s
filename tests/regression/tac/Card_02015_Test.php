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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02015_Test extends TestCase
{
    public function name(): string
    {
        return '_02015 Teeth of the Drachen';
    }

    /**
     * @return _02015
     */
    private function scene(TestWorld $world): _02015
    {
        /** @var _02015 $scheme */
        $scheme = $world->placeCard(new _02015(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Scheme with Initiative 85 and no composites' => function () {
                $scheme = new _02015();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(85, $scheme->Initiative, 'Initiative');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
            },

            'state constant registered' => function () {
                Assert::same(2602015, States::PLANNING_PHASE_RESOLVE_SCHEMES_02015, '02015');
            },

            // WHY (journal in _02015.php): "two locations with no Renown" is
            // do-as-much-as-possible — 0 empty → skip transition; else choose min(2, empty).
            'resolve with zero empty locations does not transition' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                foreach ([
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_OLES_INN,
                    Game::LOCATION_CITY_GOVERNORS_GARDEN,
                ] as $loc) {
                    $world->theah->setLocationRenown($loc, 1);
                }

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no transition');
            },

            'resolve with empty locations queues transition 02015' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02015', $transitions[0]->transition, 'name');
            },

            'args requiredLocationCount is min(2, empty count)' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);
                // 2p city typically has 5 locations; three filled → two empty.

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                    'x',
                    (string) $scheme->Id
                );
                Assert::same(2, $args['requiredLocationCount'], 'two empty → 2');
                Assert::count(2, $args['locationIds'], 'two ids');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_OLES_INN, 1);
                $argsOne = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                    'x',
                    (string) $scheme->Id
                );
                Assert::same(1, $argsOne['requiredLocationCount'], 'one empty → 1');
            },

            'actFromCardWithIds places renown on required empty locations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_OLES_INN, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_GOVERNORS_GARDEN, 1);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                    'x',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two adds');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'act rejects wrong count' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'need two');
            },

            'act rejects duplicate locations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'duplicates');
            },

            'act rejects location that already has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02015,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'has renown');
            },

            // WHY: scheme at Home blocks intervene at uncontrolled city locations.
            'eventCheck blocks intervene at uncontrolled location when scheme at Home' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $char = $world->placeCharacter(new GenericCharacter('Intervenor'), Game::LOCATION_CITY_DOCKS, 2);
                // Uncontrolled (controller 0).

                $event = new EventCharacterIntervened();
                $event->newTargetId = $char->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $scheme->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'uncontrolled blocked');
            },

            'eventCheck allows intervene at controlled location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $char = $world->placeCharacter(new GenericCharacter('Intervenor'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterIntervened();
                $event->newTargetId = $char->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $scheme->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'controlled ok');
            },

            'do-as-much-as-possible WHY pinned in source' => function () {
                $src = file_get_contents(dirname(__DIR__, 3) . '/modules/php/cards/tac/_02015.php');
                Assert::contains('do-as-much-as-possible', $src, 'WHY comment');
                Assert::contains('requiredLocationCount', $src, 'required count');
            },
        ];
    }
}
