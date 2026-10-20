<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01036_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01036';
    }

    public function tests(): array
    {
        return [
            'available with Mercenary and opposing character in city' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: Uwe counts as Mercenary when Daniella is the queryCard
            'available when Uwe counts as Mercenary for Daniella' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _01043(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'Uwe as Merc');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_PLAYER_HOME, 2);

                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable without Mercenary or without opposing' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no merc');
            },

            'trigger sets Combat challenge type and transitions 01036' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::DANIELA_DEITRICH_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same('01036', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'isValidTarget refuses own characters' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $merc->Id);

                /** @var Action_01036 $action */
                $action = $daniella->getActions()[0];
                [$ok] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ok, 'own');
            },
        ];
    }
}
