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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02025;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02025_Test extends TestCase
{
    public function name(): string
    {
        return '_02025 Tea and Cakes';
    }

    /** @return _02025 */
    private function scene(TestWorld $world): _02025
    {
        /** @var _02025 $scheme */
        $scheme = $world->placeCard(new _02025(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Scheme with Action_02025' => function () {
                $scheme = new _02025();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(19, $scheme->Initiative, 'Initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::true($scheme->hasTrait('Bargain'), 'Bargain');
                Assert::true($scheme->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_02025::class, $scheme->getActions()[0], 'Action');
            },

            'owns Action when placed' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                Assert::same($scheme->Id, $scheme->getActions()[0]->OwnerId, 'owner');
            },

            'planning state constants registered' => function () {
                Assert::same(2602025, States::PLANNING_PHASE_RESOLVE_SCHEMES_02025, 'step 1');
                Assert::same(26020252, States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2, 'step 2');
                Assert::same(26020253, States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_3, 'step 3');
            },

            // WHY: Scheme resolve → transition "02025".
            'resolve queues transition 02025 at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02025', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'act step 1 adds Renown and transitions 02025_2' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02025,
                    'planningPhaseResolveSchemes_02025',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02025_2', $transitions[0]->transition, 'name');
                Assert::same([''], $world->game->gamestate->transitions, 'empty next');
            },

            'act step 2 chooses opponent and transitions 02025_3' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithId(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2,
                    'planningPhaseResolveSchemes_02025_2',
                    (string) $scheme->Id,
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02025_3', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'opponent acts');
            },

            'act step 2 rejects self' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithId(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2,
                        'planningPhaseResolveSchemes_02025_2',
                        (string) $scheme->Id,
                        1
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'self');
            },

            'act step 3 opponent places Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->activePlayerId = 2;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_3,
                    'planningPhaseResolveSchemes_02025_3',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_FORUM]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'forum');
                Assert::same(2, $renown[0]->playerId, 'opponent');
            },

            'args step 2 lists opponents' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02025_2,
                    'planningPhaseResolveSchemes_02025_2',
                    (string) $scheme->Id
                );

                $ids = array_map(fn($o) => $o['id'], $args['opponents']);
                Assert::true(in_array(2, $ids, true), 'p2');
                Assert::false(in_array(1, $ids, true), 'not self');
            },
        ];
    }
}
