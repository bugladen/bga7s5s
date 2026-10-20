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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01152b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01152b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01152b';
    }

    /**
     * Scheme at Home; controller has a city performer; en-garde foe at same location.
     *
     * @return array{0:_01152,1:Action_01152b,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01152(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Engaged = false;
        /** @var Action_01152b $action */
        $action = $scheme->getActions()[1];
        return [$scheme, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction' => function () {
                Assert::instanceOf(SchemeCityAction::class, new Action_01152b(), 'SchemeCityAction');
            },

            'available with a city performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a city performer' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01152(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01152b $action */
                $action = $scheme->getActions()[1];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no performer');
            },

            'trigger queues transition 01152b' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01152b', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'args list non-engaged characters at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $engaged = $world->placeCharacter(new GenericCharacter('Busy'), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01152b,
                    'highDramaPlayerTurn_01152b'
                );

                $ids = $args['characterIds'];
                Assert::true(in_array($foe->Id, $ids, true), 'foe');
                Assert::true(in_array($performer->Id, $ids, true), 'performer also en garde');
                Assert::false(in_array($engaged->Id, $ids, true), 'engaged excluded');
            },

            'act wounds performer, Engages target, and resolves' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01152b,
                    'highDramaPlayerTurn_01152b',
                    $foe->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($performer->Id, $wounds[0]->characterId, 'performer');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'target');
                Assert::same($action->Id, $engages[0]->abilityId, 'ability');
                Assert::same($scheme->Id, $engages[0]->sourceId, 'scheme');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'state constant registered' => function () {
                Assert::same(4011522, States::HIGH_DRAMA_PLAYER_TURN_01152b, 'state');
            },
        ];
    }
}
