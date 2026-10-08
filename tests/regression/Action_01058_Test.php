<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01058;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01058_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01058';
    }

    private function withCombat(Character $character, int $combat): Character
    {
        $character->Combat = $combat;
        $character->ModifiedCombat = $combat;
        return $character;
    }

    /** @return array{0:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk,1:Character,2:Character} */
    private function scenario(TestWorld $world, int $performerCombat = 3, int $foeCombat = 1, array $foeTraits = []): array
    {
        $risk = $world->placeCard(new _01058(), Game::LOCATION_HAND, 1);
        $performer = $this->withCombat(
            $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1),
            $performerCombat
        );
        $foe = $this->withCombat(
            $world->placeCharacter(new GenericCharacter('Foe', $foeTraits), Game::LOCATION_CITY_DOCKS, 2),
            $foeCombat
        );
        return [$risk, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: card text says "City Action" but Action_01058 extends RiskAction, not RiskCityAction.
            // Availability is driven by getAvailablePerformers (city performer with weaker opposing
            // non-Leader at same location), not the RiskCityAction "any character in city" gate.
            'extends RiskAction not RiskCityAction' => function () {
                $risk = new _01058();
                $action = $risk->getActions()[0];
                Assert::instanceOf(\Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction::class, $action, 'RiskAction');
                Assert::false(
                    $action instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction,
                    'not RiskCityAction'
                );
            },

            'available vs opposing non-Leader with lower Combat' => function () {
                $world = new TestWorld();
                [$risk, $performer] = $this->scenario($world);

                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one performer');
                Assert::same($performer->Id, $performers[0]->Id, 'performer');
            },

            'unavailable when opposing Combat is equal' => function () {
                $world = new TestWorld();
                [$risk] = $this->scenario($world, 2, 2);
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'equal Combat');
            },

            'unavailable when opposing Combat is higher' => function () {
                $world = new TestWorld();
                [$risk] = $this->scenario($world, 1, 3);
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'higher Combat');
            },

            'unavailable when only opposing character is a Leader' => function () {
                $world = new TestWorld();
                [$risk] = $this->scenario($world, 3, 1, ['Leader']);
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Leader excluded');
            },

            'unavailable when the lower-Combat character is friendly' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01058(), Game::LOCATION_HAND, 1);
                $this->withCombat($world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1), 3);
                $this->withCombat($world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1), 1);
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'friendly only');
            },

            'unavailable when card is not in hand' => function () {
                $world = new TestWorld();
                [$risk] = $this->scenario($world);
                $risk->Location = Game::LOCATION_CITY_DISCARD;
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01058' => function () {
                $world = new TestWorld();
                [$risk] = $this->scenario($world);
                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01058', $transitions[0]->transition, 'transition');
            },

            'args list only valid opposing targets' => function () {
                $world = new TestWorld();
                [$risk, $performer, $foe] = $this->scenario($world);
                $this->withCombat($world->placeCharacter(new GenericCharacter('Strong'), Game::LOCATION_CITY_DOCKS, 2), 5);
                $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01058, 'highDramaPhase01058');
                Assert::same($performer->Id, $args['performerId'], 'performerId');
                Assert::same([$foe->Id], $args['characterIds'], 'only weaker non-Leader');
            },

            'act on ready target engages and moves Home engaged' => function () {
                $world = new TestWorld();
                [$risk, $performer, $foe] = $this->scenario($world);
                $foe->Engaged = false;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01058,
                    'highDramaPhase01058',
                    $foe->Id
                );

                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engage, 'engage');
                Assert::same($foe->Id, $engage[0]->cardId, 'engaged foe');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'moved foe');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::true($moves[0]->engage, 'engage flag');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->game->gamestate->transitions, 'nextState called');
            },

            'act on already engaged target skips engage event' => function () {
                $world = new TestWorld();
                [$risk, $performer, $foe] = $this->scenario($world);
                $foe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01058,
                    'highDramaPhase01058',
                    $foe->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act rejects Leader and equal-Combat targets' => function () {
                $world = new TestWorld();
                [$risk, $performer, $foe] = $this->scenario($world, 3, 3);
                $leader = $this->withCombat(
                    $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_DOCKS, 2),
                    1
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01058 $action */
                $action = $risk->getActions()[0];
                foreach ([$foe, $leader] as $target) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId(
                            $world->game,
                            States::HIGH_DRAMA_PLAYER_TURN_01058,
                            'highDramaPhase01058',
                            $target->Id
                        );
                    } catch (\Throwable $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejects ' . $target->Name);
                }
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
