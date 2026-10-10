<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01160_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01160';
    }

    /**
     * Bleed Out in hand; wounded non-Leader in city.
     *
     * @return array{0:_01160,1:Action_01160,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 1);
        $target = $world->placeCharacter(new GenericCharacter('Bleeder'), Game::LOCATION_CITY_DOCKS, 2);
        $target->Wounds = 1;
        /** @var Action_01160 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $target];
    }

    private function placeLeader(TestWorld $world, array $extraTraits = []): GenericLeader
    {
        $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
        // WHY: hasTrait reads ModifiedTraits (snapshotted at resetCard) — append both.
        foreach ($extraTraits as $trait) {
            $leader->Traits[] = $trait;
            $leader->ModifiedTraits[] = $trait;
        }
        $world->theah->leadersByPlayerId[1] = $leader;
        return $leader;
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets characters' => function () {
                $action = new Action_01160();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            'available when a wounded non-Leader is in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the only wounded character is a Leader' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new GenericLeader('Hurt Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $leader->Wounds = 1;
                /** @var Action_01160 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader only');
            },

            'unavailable when wounded characters are only at Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 1);
                $home = $world->placeCharacter(new GenericCharacter('Home Hurt'), Game::LOCATION_PLAYER_HOME, 2);
                $home->Wounds = 1;
                /** @var Action_01160 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home only');
            },

            'unavailable when no one is wounded' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $target->Wounds = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unwounded');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            // WHY: Pattern E — Villain Leader -1 cost on hand Action only (no Maneuver channel).
            'Villain Leader discounts this Action by 1' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->placeLeader($world, ['Villain']);

                $explanations = [];
                $discount = $action->getActionFromHandDiscount($world->theah, null, $action, $explanations);
                Assert::same(1, $discount, 'Villain');
                Assert::count(1, $explanations, 'explained');
            },

            'non-Villain Leader grants no discount' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $this->placeLeader($world, ['Hero']);

                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'no Villain');
            },

            'trigger queues transition 01160' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01160', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            // FLAG candidate pinned: getArgs lists wounded non-Leaders from getCharactersInPlay
            // without cardInCity — Home wounded non-Leaders appear in args even though
            // isAvailableToPlayer / isValidTargetForAbility require city. Act still refuses them.
            'args include wounded non-Leaders in play (including Home)' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home Hurt'), Game::LOCATION_PLAYER_HOME, 2);
                $home->Wounds = 1;
                $leader = $world->placeCharacter(new GenericLeader('Hurt Leader'), Game::LOCATION_CITY_FORUM, 2);
                $leader->Wounds = 1;

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01160,
                    'highDramaPlayerTurn_01160'
                );

                Assert::true(in_array($target->Id, $args['ids'], true), 'city wounded');
                Assert::true(in_array($home->Id, $args['ids'], true), 'home wounded in args');
                Assert::false(in_array($leader->Id, $args['ids'], true), 'leader excluded');
            },

            'selecting a valid target queues wound and ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $target] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01160,
                    'highDramaPlayerTurn_01160',
                    $target->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($target->Id, $wounds[0]->characterId, 'target');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($action->Id, $wounds[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'refuses an unwounded character' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $target->Wounds = 0;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01160,
                        'highDramaPlayerTurn_01160',
                        $target->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'unwounded');
            },

            'refuses a Leader' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Hurt Leader'), Game::LOCATION_CITY_FORUM, 2);
                $leader->Wounds = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01160,
                        'highDramaPlayerTurn_01160',
                        $leader->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'leader');
            },

            'refuses a wounded character at Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home Hurt'), Game::LOCATION_PLAYER_HOME, 2);
                $home->Wounds = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01160,
                        'highDramaPlayerTurn_01160',
                        $home->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'home');
            },

            'isValidTargetForAbility mirrors city / wounded / non-Leader gates' => function () {
                $world = new TestWorld();
                [, $action, $target] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);
                $home->Wounds = 1;

                [$ok, ] = $action->isValidTargetForAbility($world->game, $target);
                Assert::true($ok, 'city wounded');
                [$okHome, ] = $action->isValidTargetForAbility($world->game, $home);
                Assert::false($okHome, 'home');
            },

            'state constant registered' => function () {
                Assert::same(401160, States::HIGH_DRAMA_PLAYER_TURN_01160, '01160');
            },
        ];
    }
}
