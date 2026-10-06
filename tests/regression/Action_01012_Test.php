<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01012_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01012';
    }

    public function tests(): array
    {
        return [
            'available in city with Sorcerer and opposing character here' => function () {
                $world = new TestWorld();
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01012 $action */
                $action = $sibella->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without opposing character or Sorcerer trait' => function () {
                $world = new TestWorld();
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01012 $action */
                $action = $sibella->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no foe');

                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                // hasTrait reads ModifiedTraits (seeded from Traits at reset)
                $sibella->ModifiedTraits = array_values(array_filter(
                    $sibella->ModifiedTraits,
                    fn($t) => $t !== 'Sorcerer'
                ));
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'lost Sorcerer');
            },

            // WHY regression: audit 2026-04-13 — isValidTargetForAbility must reject same controller
            'isValidTargetForAbility rejects ally and off-location foe' => function () {
                $world = new TestWorld();
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $farFoe = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_FORUM, 2);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01012 $action */
                $action = $sibella->getActions()[0];
                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally rejected');
                Assert::false($action->isValidTargetForAbility($world->game, $farFoe)[0], 'far foe rejected');
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'local foe ok');
            },

            'trigger queues transition 01012' => function () {
                $world = new TestWorld();
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01012 $action */
                $action = $sibella->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01012', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'act wounds Sibella then target and fires sorcerer + resolved events' => function () {
                $world = new TestWorld();
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01012 $action */
                $action = $sibella->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01012,
                    'highDramaPhase01012',
                    $foe->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'two wound events');
                Assert::same($sibella->Id, $wounds[0]->characterId, 'Sibella wounded first (cost)');
                Assert::same($foe->Id, $wounds[1]->characterId, 'target wounded second');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'sorcerer start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'sorcerer played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'action resolved');
                Assert::same(['opposingCharacterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
