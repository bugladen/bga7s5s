<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01152;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01152a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01152a';
    }

    /**
     * Scheme at Home; controller has a city performer; engaged foe at same location.
     *
     * @return array{0:_01152,1:Action_01152a,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01152(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Engaged = true;
        /** @var Action_01152a $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction' => function () {
                Assert::instanceOf(SchemeCityAction::class, new Action_01152a(), 'SchemeCityAction');
            },

            'available with a city performer even when nobody is engaged' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Engaged = false;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when scheme is not at Player Home' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);
                $scheme->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not home');
            },

            'unavailable without a city performer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01152(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01152a $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no performer');
            },

            'trigger queues transition 01152a' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01152a', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'args list all characters at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $enGarde = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);
                $elsewhere->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01152a,
                    'highDramaPlayerTurn_01152a'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                $ids = $args['characterIds'];
                Assert::true(in_array($foe->Id, $ids, true), 'engaged foe');
                Assert::true(in_array($enGarde->Id, $ids, true), 'en garde included');
                Assert::true(in_array($performer->Id, $ids, true), 'performer included');
                Assert::false(in_array($elsewhere->Id, $ids, true), 'other loc excluded');
            },

            // WHY: En Garde = EventCardEngarded (not EventCardEngaged / Engage).
            'act wounds performer, En Gardes engaged target, and resolves' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01152a,
                    'highDramaPlayerTurn_01152a',
                    $foe->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($performer->Id, $wounds[0]->characterId, 'performer');
                Assert::same(1, $wounds[0]->wounds, '1');
                Assert::same($scheme->Id, $wounds[0]->sourceId, 'scheme source');

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($foe->Id, $engardes[0]->cardId, 'target');
                Assert::same($action->Id, $engardes[0]->abilityId, 'ability');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'act wounds performer and skips En Garde when target is already en garde' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $foe->Engaged = false;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01152a,
                    'highDramaPlayerTurn_01152a',
                    $foe->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($performer->Id, $wounds[0]->characterId, 'performer');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no engarde');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'state constant registered' => function () {
                Assert::same(4011521, States::HIGH_DRAMA_PLAYER_TURN_01152a, 'state');
            },
        ];
    }
}
