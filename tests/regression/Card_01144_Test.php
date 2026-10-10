<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01144;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01144;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01144_Test extends TestCase
{
    public function name(): string
    {
        return '_01144 Filling The Ranks';
    }

    private function revealed(TestWorld $world): _01144
    {
        /** @var _01144 $scheme */
        $scheme = $world->placeCard(new _01144(), Game::LOCATION_PLAYER_HOME, 1);
        $world->game->activePlayerId = 1;
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Bargain Conscription Scheme with Reaction_01144' => function () {
                $scheme = new _01144();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(50, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Bargain'), 'Bargain');
                Assert::true($scheme->hasTrait('Conscription'), 'Conscription');
                Assert::instanceOf(Reaction_01144::class, $scheme->getReactions()[0], 'Reaction_01144');
            },

            'resolving queues the 01144 transition at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01144', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            // WHY (journal 2026-09-24-02): empty/null ids used to TypeError on createRenownAddedToLocationEvent.
            'step 1 refuses null or missing location' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
                        'planningPhaseResolveSchemes_01144',
                        '',
                        []
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'step 1 with unique fewest Renown transitions to fewestReknown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->playerScores = [1 => 1, 2 => 5];

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
                    'planningPhaseResolveSchemes_01144',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::count(1, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'Renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'chosen');
                Assert::same(['fewestReknown'], $world->game->gamestate->transitions, 'fewest');
            },

            'step 1 with tied fewest Renown transitions to notFewestReknown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->playerScores = [1 => 2, 2 => 2];

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
                    'x',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(['notFewestReknown'], $world->game->gamestate->transitions, 'tied');
            },

            'step 1 when active player is not the unique fewest goes to notFewestReknown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->playerScores = [1 => 5, 2 => 1];

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
                    'x',
                    '',
                    [Game::LOCATION_CITY_BAZAAR]
                );

                Assert::same(['notFewestReknown'], $world->game->gamestate->transitions, 'not fewest');
            },

            'args for step 2 expose the first chosen location' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                    'planningPhaseResolveSchemes_01144_2',
                    ''
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $args['location'], 'first location');
            },

            'step 2 refuses the same location as step 1' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                        'x',
                        '',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same location refused');
            },

            'step 2 adds Renown to a different location' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                    'x',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'Renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'forum');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },
        ];
    }
}
