<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01150;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01150_Test extends TestCase
{
    public function name(): string
    {
        return '_01150 Parley Gone Wrong';
    }

    /** @return _01150 */
    private function revealed(TestWorld $world): _01150
    {
        /** @var _01150 $scheme */
        $scheme = $world->placeCard(new _01150(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    private function intervene(TestWorld $world, int $playerId, int $oldTargetId, int $newTargetId): EventCharacterIntervened
    {
        $event = new EventCharacterIntervened();
        $event->playerId = $playerId;
        $event->oldTargetId = $oldTargetId;
        $event->newTargetId = $newTargetId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Feud Provocation Scheme with empty interveneList' => function () {
                $scheme = new _01150();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(55, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Feud'), 'Feud');
                Assert::true($scheme->hasTrait('Provocation'), 'Provocation');
                Assert::same([], $scheme->interveneList, 'empty list');
            },

            'resolving adds Forum Renown and queues opponent 01150 transitions' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'forum renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'forum');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one opponent');
                Assert::same('01150', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'opponent');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'adding Renown to Forum appends player to interveneList' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventRenownAddedToLocation();
                $event->playerId = 2;
                $event->location = Game::LOCATION_CITY_FORUM;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::same([2], $scheme->interveneList, 'listed');
                Assert::true($scheme->IsUpdated, 'dirty');
            },

            'playerId 0 does not enter interveneList' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventRenownAddedToLocation();
                $event->playerId = 0;
                $event->location = Game::LOCATION_CITY_FORUM;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::same([], $scheme->interveneList, 'anonymous ignored');
            },

            'duplicate Forum Renown adds do not double-list a player' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->interveneList = [2];

                $event = new EventRenownAddedToLocation();
                $event->playerId = 2;
                $event->location = Game::LOCATION_CITY_FORUM;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::same([2], $scheme->interveneList, 'once');
            },

            'Renown added elsewhere does not grant intervene' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventRenownAddedToLocation();
                $event->playerId = 2;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::same([], $scheme->interveneList, 'not forum');
            },

            'Dusk clears interveneList' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->interveneList = [1, 2];

                $dusk = new EventDuskEndOfDay();
                $world->fireOn($scheme, $dusk);

                Assert::same([], $scheme->interveneList, 'cleared');
            },

            'eventCheck blocks Forum intervene when player is not on the list' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $old = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 1);
                $new = $world->placeCharacter(new GenericCharacter('Intervenor'), Game::LOCATION_CITY_FORUM, 2);

                $threw = false;
                try {
                    $scheme->eventCheck($this->intervene($world, 2, $old->Id, $new->Id));
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'eventCheck allows Forum intervene for listed players' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->interveneList = [2];
                $old = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 1);
                $new = $world->placeCharacter(new GenericCharacter('Intervenor'), Game::LOCATION_CITY_FORUM, 2);

                $scheme->eventCheck($this->intervene($world, 2, $old->Id, $new->Id));
                Assert::true(true, 'allowed');
            },

            'eventCheck does not gate intervene outside the Forum' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $old = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 1);
                $new = $world->placeCharacter(new GenericCharacter('Intervenor'), Game::LOCATION_CITY_DOCKS, 2);

                $scheme->eventCheck($this->intervene($world, 2, $old->Id, $new->Id));
                Assert::true(true, 'unguarded');
            },

            // WHY (production comment): HIGH_PRIORITY remove/add so the next opponent's
            // MEDIUM_PRIORITY choose-location transition sees post-move renown — avoids
            // multiple opponents depleting the same location into the negatives.
            'choosing a location queues HIGH_PRIORITY move/remove/add Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->activePlayerId = 2;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01150,
                    'planningPhaseResolveSchemes_01150',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::count(1, $removed, 'removed');
                Assert::count(1, $added, 'added');
                Assert::same(Event::HIGH_PRIORITY, $moving[0]->priority, 'move high');
                Assert::same(Event::HIGH_PRIORITY, $removed[0]->priority, 'remove high');
                Assert::same(Event::HIGH_PRIORITY, $added[0]->priority, 'add high');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'to forum');
                Assert::true($added[0]->isMove, 'isMove');
                Assert::same([''], $world->game->gamestate->transitions, 'empty');
            },

            'pass advances without queuing Renown moves' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $world->game->activePlayerId = 2;

                $scheme->actFromCardPass(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01150,
                    'planningPhaseResolveSchemes_01150',
                    ''
                );

                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');
                // WHY: actFromCardPass calls bare nextState() — FakeGamestate records null.
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'getInterveneListData shapes UI rows' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->interveneList = [2];

                $data = $scheme->getInterveneListData($world->game);
                Assert::count(1, $data, 'one');
                Assert::same(2, $data[0]['playerId'], 'id');
                Assert::same('Player Two', $data[0]['playerName'], 'name');
                Assert::true(isset($data[0]['playerColor']), 'color');
            },

            'state constant registered' => function () {
                Assert::same(2601150, States::PLANNING_PHASE_RESOLVE_SCHEMES_01150, 'scheme state');
            },
        ];
    }
}
