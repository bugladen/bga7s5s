<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01015_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01015';
    }

    public function tests(): array
    {
        return [
            'available when scheme at Home and performer shares location with another character' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY regression: audit 2026-04-13 — may wound ANY character at location (incl. own)
            'available when only other character is own ally' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'own ally is valid company');
            },

            'unavailable with lone character in city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Alone'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no other character');
            },

            'isValidTargetForAbility allows ally and foe; rejects performer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isValidTargetForAbility($world->game, $performer)[0], 'performer rejected');
                Assert::true($action->isValidTargetForAbility($world->game, $ally)[0], 'ally ok');
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe ok');
            },

            'args list all characters at performer location except performer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01015,
                    'highDramaPhase01015'
                );

                sort($args['ids']);
                $expected = [$ally->Id, $foe->Id];
                sort($expected);
                Assert::same($expected, $args['ids'], 'ally and foe listed');
            },

            'trigger queues transition 01015' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01015', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act destroys performer and wounds target' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01015(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01015 $action */
                $action = $scheme->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01015,
                    'highDramaPhase01015',
                    $ally->Id
                );

                $destroys = $world->theah->queuedOfType(EventCharacterDestroyed::class);
                Assert::count(1, $destroys, 'destroy');
                Assert::same($performer->Id, $destroys[0]->characterId, 'performer destroyed');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($ally->Id, $wounds[0]->characterId, 'ally wounded');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
