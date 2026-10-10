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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01152;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01152_Test extends TestCase
{
    public function name(): string
    {
        return '_01152 Until Morale Improves';
    }

    /** @return _01152 */
    private function revealed(TestWorld $world): _01152
    {
        /** @var _01152 $scheme */
        $scheme = $world->placeCard(new _01152(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Ad Hoc Demoralize Scheme with both City Actions' => function () {
                $scheme = new _01152();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(30, $scheme->Initiative, 'Initiative');
                Assert::same(-2, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Ad Hoc'), 'Ad Hoc');
                Assert::true($scheme->hasTrait('Demoralize'), 'Demoralize');
                Assert::count(2, $scheme->getActions(), 'two actions');
                Assert::instanceOf(Action_01152a::class, $scheme->getActions()[0], '01152a');
                Assert::instanceOf(Action_01152b::class, $scheme->getActions()[1], '01152b');
            },

            'action ids stamped once placed' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                Assert::same($scheme->Id . '_Action_01152a', $scheme->getActions()[0]->Id, 'a');
                Assert::same($scheme->Id . '_Action_01152b', $scheme->getActions()[1]->Id, 'b');
            },

            'resolving queues MEDIUM transition 01152' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01152', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'args canMoveRenown is true when any city location has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                    'planningPhaseResolveSchemes_01152',
                    ''
                );
                Assert::true($args['canMoveRenown'], 'can move');
            },

            'args canMoveRenown is false when board has no Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                    'planningPhaseResolveSchemes_01152',
                    ''
                );
                Assert::false($args['canMoveRenown'], 'must place');
            },

            'placing Renown queues add and transitions reknownPlaced' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->activePlayerId = 1;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                    'planningPhaseResolveSchemes_01152',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'add');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');
                Assert::same(['reknownPlaced'], $world->game->gamestate->transitions, 'transition');
            },

            'pass from place step when Renown exists advances via pass' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $scheme->actFromCardPass(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                    'planningPhaseResolveSchemes_01152',
                    ''
                );

                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass to move');
            },

            'pass from place step with no Renown throws' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $threw = false;
                try {
                    $scheme->actFromCardPass(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                        'planningPhaseResolveSchemes_01152',
                        ''
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must place');
            },

            'move step 3 queues batched remove/add/moving Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3,
                    'planningPhaseResolveSchemes_01152_3',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::count(1, $removed, 'removed');
                Assert::count(1, $added, 'added');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'same batch');
                Assert::same($moving[0]->batchId, $added[0]->batchId, 'add same batch');
                Assert::true($added[0]->isMove, 'isMove');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'done');
            },

            'state constants registered' => function () {
                Assert::same(2601152, States::PLANNING_PHASE_RESOLVE_SCHEMES_01152, 'place');
                Assert::same(26011522, States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2, 'from');
                Assert::same(26011523, States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3, 'to');
            },
        ];
    }
}
