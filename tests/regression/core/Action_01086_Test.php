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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01086;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01086_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01086';
    }

    /**
     * Player 1 holds Status Matters; player 1 controls Docks (empty).
     *
     * @return array{0:_01086,1:Action_01086}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01086(), Game::LOCATION_HAND, 1);
        $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

        /** @var Action_01086 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action];
    }

    private function locationIds(TestWorld $world, Action_01086 $action): array
    {
        $world->game->activePlayerId = 1;
        $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01086, 'x');
        return $args['locationIds'];
    }

    public function tests(): array
    {
        return [
            // WHY: card text says "Action", not City Action — no city/performer gate. Base is RiskAction.
            'is a plain RiskAction' => function () {
                Assert::instanceOf(RiskAction::class, new Action_01086(), 'RiskAction');
            },

            'available when a controlled location has no characters' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'empty controlled');
                Assert::same([Game::LOCATION_CITY_DOCKS], $this->locationIds($world, $action), 'Docks');
            },

            'available when a controlled location holds only Mercenaries' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Merc A', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Merc B', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'only Mercenaries');
                Assert::same([Game::LOCATION_CITY_DOCKS], $this->locationIds($world, $action), 'Docks');
            },

            // WHY: "no characters or only Mercenaries" — a single non-Mercenary (either side) blocks the location.
            'unavailable when a non-Mercenary shares the location with Mercenaries' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Regular'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'mixed');
                Assert::same([], $this->locationIds($world, $action), 'no locations');
            },

            'unavailable when only a non-Mercenary is at the location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Regular'), Game::LOCATION_CITY_DOCKS, 1);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'non-Mercenary');
            },

            // WHY: getCharactersAtLocation skips uncontrolled characters, so a stray does not block.
            'an uncontrolled character does not block the location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Stray'), Game::LOCATION_CITY_DOCKS, 0);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled ignored');
            },

            'unavailable when no location is controlled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to uncontrol');
            },

            // WHY: Indomitable Will (Action_01130) toggles CanBecomeUncontrolled on the location.
            'unavailable when the location cannot become uncontrolled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'protected');
                Assert::same([], $this->locationIds($world, $action), 'no locations');
            },

            'an opponent-controlled empty location is a valid target' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);

                Assert::same([Game::LOCATION_CITY_FORUM], $this->locationIds($world, $action), 'any controller');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01086' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01086', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
            },

            'choosing a location queues Uncontrolled then ActionResolved and advances' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01086, 'x', [Game::LOCATION_CITY_DOCKS]);

                $queued = $world->theah->queuedEvents;
                Assert::count(2, $queued, 'uncontrolled + resolved');
                Assert::instanceOf(EventLocationBecomesUncontrolled::class, $queued[0], 'uncontrolled first');
                Assert::same(Game::LOCATION_CITY_DOCKS, $queued[0]->location, 'location');
                Assert::same(1, $queued[0]->playerId, 'owner');
                Assert::instanceOf(EventActionResolved::class, $queued[1], 'resolved second');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            // WHY: still resolves the Action (cost paid) but must not queue an Uncontrolled event
            // for a location that Indomitable Will protects; players get a log line instead.
            'choosing a protected location logs a message, still resolves, and queues no Uncontrolled' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBecomeUncontrolled = false;

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01086, 'x', [Game::LOCATION_CITY_DOCKS]);

                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'blocked');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $messages = $world->game->notify->messages;
                Assert::true(count($messages) >= 1, 'message logged');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            // WHY: empty/Mercenary was only checked in isAvailable/args; act must re-check against crafted clients.
            'act refuses a location with a non-Mercenary character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Citizen'), Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01086, 'x', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
