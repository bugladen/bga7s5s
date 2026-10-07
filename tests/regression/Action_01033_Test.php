<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01033_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01033';
    }

    public function tests(): array
    {
        return [
            'available with performer facing opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01033 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Action_01033 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'performers exclude dashed Influence' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $dashed = $world->placeCharacter(new GenericCharacter('Dashed'), Game::LOCATION_CITY_DOCKS, 1);
                $dashed->DashedInfluence = true;
                $ok = $world->placeCharacter(new GenericCharacter('Ok'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Foe1'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Foe2'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01033 $action */
                $action = $risk->getActions()[0];
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($ok->Id, $ids, true), 'ok performer');
                Assert::false(in_array($dashed->Id, $ids, true), 'dashed excluded');
            },

            'isValidTargetForAbility requires opposing character at same location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                /** @var Action_01033 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
                Assert::false($action->isValidTargetForAbility($world->game, $performer)[0], 'self');
            },

            'trigger sets Influence challenge and Veronica type then queues 01033' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);
                /** @var Action_01033 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(
                    Game::VERONICAS_GUILLE_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'type'
                );
                Assert::same('01033', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },
        ];
    }
}
