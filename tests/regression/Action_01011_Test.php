<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01011_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01011';
    }

    public function tests(): array
    {
        return [
            'available when own Red Hand faces foe at adjacent location' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Foe'),
                    Game::LOCATION_CITY_FORUM,
                    2
                );

                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent Red Hand with foe');
            },

            // WHY regression: audit — getTargetCharacters must use same adjacency filter as isAvailable
            'unavailable when Red Hand with foe is non-adjacent' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                // 2p: Docks adjacent only to Forum, not Bazaar
                $world->placeCharacter(
                    new GenericCharacter('Far RH', ['Red Hand']),
                    Game::LOCATION_CITY_BAZAAR,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Foe'),
                    Game::LOCATION_CITY_BAZAAR,
                    2
                );

                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Bazaar not adjacent to Docks');
            },

            'unavailable when Servo engaged or at Home' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];
                $servo->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');

                $servo->Engaged = false;
                $servo->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in city');
            },

            'args only list foes opposing adjacent Red Hands' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Near RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $nearFoe = $world->placeCharacter(new GenericCharacter('Near Foe'), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(
                    new GenericCharacter('Far RH', ['Red Hand']),
                    Game::LOCATION_CITY_BAZAAR,
                    1
                );
                $farFoe = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_BAZAAR, 2);

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $servo->Id);

                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];
                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01011,
                    'highDramaPhase01011'
                );

                Assert::same([$nearFoe->Id], $args['characterIds'], 'only adjacent Forum foe');
                Assert::false(in_array($farFoe->Id, $args['characterIds'], true), 'far foe excluded');
            },

            'trigger queues transition 01011' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01011', $transitions[0]->transition, 'transition');
            },

            'act moves Servo to target and sets Servo challenge type' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01011 $action */
                $action = $servo->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01011,
                    'highDramaPhase01011',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::SERVO_SCARPA_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'challenge type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat stat');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move queued');
                Assert::same($servo->Id, $moves[0]->cardId, 'Servo moves');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to Forum');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01011_2', $transitions[0]->transition, 'challenge follow-up');
                Assert::same(['opposingCharacterChosen'], $world->game->gamestate->transitions, 'nextState');
            },
        ];
    }
}
