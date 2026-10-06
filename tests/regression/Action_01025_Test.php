<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01025_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01025';
    }

    public function tests(): array
    {
        return [
            'available with Sorcerer Strega facing opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01025(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01025 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Sorcerer Strega or without opposing character' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01025(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                /** @var Action_01025 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no foe');

                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(
                    new GenericCharacter('Not Strega', ['Sorcerer']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                // Remove the Strega by moving home alone — still have Strega+foe at Docks so true.
                // Instead strip Strega trait:
                $strega = null;
                foreach ($world->theah->getCharactersInPlayByPlayerId(1) as $c) {
                    if ($c->hasTrait('Strega')) {
                        $strega = $c;
                        break;
                    }
                }
                $strega->ModifiedTraits = array_values(array_filter(
                    $strega->ModifiedTraits,
                    fn($t) => $t !== 'Strega'
                ));
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'lost Strega');
            },

            'trigger queues transition 01025' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01025(), Game::LOCATION_HAND, 1);
                /** @var Action_01025 $action */
                $action = $risk->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01025', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'args list opposing characters at performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01025(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                /** @var Action_01025 $action */
                $action = $risk->getActions()[0];
                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01025,
                    'highDramaPhase01025'
                );

                Assert::same([$foe->Id], $args['ids'], 'only opposing');
            },

            'act creates risk attachment and fires sorcerer events' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01025(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                /** @var Action_01025 $action */
                $action = $risk->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01025,
                    'highDramaPhase01025',
                    $foe->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->game->createdRiskAttachments, 'createRiskAttachment called');
                Assert::same('01025_Burden', $world->game->createdRiskAttachments[0]['className'], 'burden class');
                Assert::same($foe->Id, $world->game->createdRiskAttachments[0]['targetId'], 'target');
            },
        ];
    }
}
