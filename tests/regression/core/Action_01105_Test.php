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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01105_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01105';
    }

    /**
     * @return array{0:_01105,1:Action_01105,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01105(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01105 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $mine, $foe];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that targets characters' => function () {
                $action = new Action_01105();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available with a city character and Risk in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with no city characters' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01105(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01105 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no city');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger starts Resolve pressure and transitions to pressureLocation' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal');
                Assert::same(Game::STAT_RESOLVE, $world->game->globals->get(Game::PRESSURE_STAT), 'Resolve');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::true(in_array(Game::STAT_RESOLVE, $pressure[0]->pressureTypes, true), 'Resolve type');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'pressure success queues transition 01105 for engage choice' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01105', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'pressure failure does nothing (no engage chooser)' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'failure ignored here');
            },

            'args list opposing characters at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01105,
                    'highDramaPlayerTurn_01105'
                );

                Assert::same($mine->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'only local foe');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far');
            },

            'act engages the target and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01105,
                    'highDramaPlayerTurn_01105',
                    $foe->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'foe');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act refuses an already engaged target' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $foe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);
                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01105,
                        'x',
                        $foe->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'already engaged');
            },

            'pass resolves without engaging' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01105);

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
