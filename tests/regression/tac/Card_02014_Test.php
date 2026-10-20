<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02014_Test extends TestCase
{
    public function name(): string
    {
        return "_02014 Kaspar's Occupation";
    }

    /**
     * @return _02014
     */
    private function scene(TestWorld $world): _02014
    {
        /** @var _02014 $scheme */
        $scheme = $world->placeCard(new _02014(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Scheme with Action' => function () {
                $scheme = new _02014();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(70, $scheme->Initiative, 'Initiative');
                Assert::true($scheme->hasTrait('Logistics'), 'Logistics');
                Assert::true($scheme->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_02014::class, $scheme->getActions()[0], 'Action');
            },

            'owns Action when placed' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                Assert::same($scheme->Id, $scheme->getActions()[0]->OwnerId, 'owner');
            },

            'state constant registered' => function () {
                Assert::same(2602014, States::PLANNING_PHASE_RESOLVE_SCHEMES_02014, '02014');
            },

            'resolve queues transition 02014' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02014', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: id=1 = add a new Renown to Forum (vs move-from-elsewhere path).
            'act id=1 adds renown to Forum' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithId(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02014,
                    'x',
                    (string) $scheme->Id,
                    1
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'add');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'Forum');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'actFromCardWithIds moves renown from another location to Forum' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02014,
                    'x',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::count(1, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'moving');
                Assert::count(1, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'removed');
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'to Forum');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            // WHY: sprintf($msg, $locationName) — not UserException($msg, $code).
            'refuses move FROM Forum' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02014,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Forum source');
            },

            'refuses move from location with zero renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02014,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'zero renown');
            },
        ];
    }
}
