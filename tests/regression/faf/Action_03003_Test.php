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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03003_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03003';
    }

    public function tests(): array
    {
        return [
            'available when own Thug faces foe at Don location' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: no Engage printed — already-engaged Thugs remain eligible
            'available when Thug is already engaged' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $thug->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged Thug ok');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_PLAYER_HOME, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'trigger queues transition 03003' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('03003', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY regression: prior code conditionally engaged unengaged Thugs even
            // though print has no Engage cost. Challenge type keeps Thug off
            // stIssueChallenge auto-engage; action must emit no engage either.
            'target pick issues Combat challenge without engaging Thug' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $thug->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_03003_2,
                    'highDramaPhase03003_2',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(
                    Game::DON_CONSTANZO_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'type off auto-engage'
                );
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::same('03003_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'Thug args include an already-engaged Thug at Don location and skip one elsewhere' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                $thug->Engaged = true;
                $away = $world->placeCharacter(new GenericCharacter('Away', ['Thug']), Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03003, 'highDramaPhase03003');
                Assert::same($don->Id, $args['donId'], 'don');
                Assert::same([$thug->Id], $args['ids'], 'engaged thug still listed');
                Assert::false(in_array($away->Id, $args['ids'], true), 'other location');
            },

            'choosing a Thug stores the performer and does not issue the challenge yet' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03003, 'x', $thug->Id);
                Assert::same($thug->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same(['thugChosen'], $world->game->gamestate->transitions, 'transition');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'not issued yet');
            },

            'a non-Thug or a Thug with nobody to challenge is rejected' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 1);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03003, 'x', $civilian->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not a thug');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03003, 'x', $lonely->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'no opposing character');
                unset($don);
            },

            'target list follows the chosen Thug, and a Leader is legal' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_FORUM, 1);
                $leader = $world->placeCharacter(new \Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader(), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(new GenericCharacter('Docks'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $thug->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03003_2, 'highDramaPhase03003_2');
                Assert::same([$leader->Id], $args['ids'], 'foe at the Thug, not at Don');
                [$ok] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::true($ok, 'leader');
            },

            'target validation fails before a performer is chosen' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03003 $action */
                $action = $don->getActions()[0];
                [$valid, $message] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($valid, 'no performer');
                Assert::contains('Performer', $message, 'message');
            },
        ];
    }
}
