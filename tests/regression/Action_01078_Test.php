<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Enemy that cannot issue challenges (e.g. a character with a challenge restriction). */
class Action01078NoChallengeCharacter extends GenericCharacter
{
    public function canChallenge(Theah $theah): bool
    {
        return false;
    }
}

class Action_01078_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01078';
    }

    /** @return array{0:_01078,1:Action_01078,2:Character,3:Character} risk, action, friendly, enemy (both at Docks) */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01078(), Game::LOCATION_HAND, 1);
        $friendly = $world->placeCharacter(new GenericCharacter('Friendly'), Game::LOCATION_CITY_DOCKS, 1);
        $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01078 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $friendly, $enemy];
    }

    private function trigger(TestWorld $world, Action_01078 $action, int $playerId = 1): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'targets characters, not cards (hook forbids implementing both)' => function () {
                $action = new Action_01078();
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::false($action instanceof IAbilityThatTargetsCards, 'not cards');
            },

            'available when an enemy in the city can challenge and a friendly opposes them' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the Risk is not in hand (unless override)' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },

            'unavailable when the enemy has no friendly opposing them' => function () {
                $world = new TestWorld();
                [, $action, $friendly] = $this->scene($world);
                $friendly->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'friendly elsewhere');
            },

            'unavailable when the only enemy is at Player Home (not in the city)' => function () {
                $world = new TestWorld();
                [, $action, , $enemy] = $this->scene($world);
                $enemy->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'enemy at home');
            },

            'unavailable when the enemy cannot challenge' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01078(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Friendly'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new Action01078NoChallengeCharacter('Pacifist'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01078 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot challenge');
            },

            'unavailable when the only enemy belongs to the player themself' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01078(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Friendly'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($risk->getActions()[0]->isAvailableToPlayer(1, $world->theah), 'no enemies');
            },

            // WHY: "Target enemy character" - the ENEMY is the performer (they issue the challenge), not the player's own piece.
            'performers are the qualifying enemy characters only' => function () {
                $world = new TestWorld();
                [, $action, , $enemy] = $this->scene($world);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely Enemy'), Game::LOCATION_CITY_FORUM, 2);
                $homeEnemy = $world->placeCharacter(new GenericCharacter('Home Enemy'), Game::LOCATION_PLAYER_HOME, 2);

                $performers = $action->getPerformersForAction(1, $world->theah);

                $ids = array_map(fn($c) => $c->Id, array_values($performers));
                Assert::same([$enemy->Id], $ids, 'only the enemy opposed by a friendly');
                Assert::false(in_array($lonely->Id, $ids, true), 'unopposed enemy excluded');
                Assert::false(in_array($homeEnemy->Id, $ids, true), 'home enemy excluded');
            },

            'isValidTargetForAbility accepts a qualifying enemy' => function () {
                $world = new TestWorld();
                [, $action, , $enemy] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $enemy);
                Assert::true($ok, 'valid');
            },

            'isValidTargetForAbility rejects own, unopposed, home and non-challenging targets' => function () {
                $world = new TestWorld();
                [, $action, $friendly] = $this->scene($world);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_FORUM, 2);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);
                $pacifist = $world->placeCharacter(new Action01078NoChallengeCharacter('Pacifist'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::false($action->isValidTargetForAbility($world->game, $friendly)[0], 'own character');
                Assert::false($action->isValidTargetForAbility($world->game, $lonely)[0], 'no friendly opposing');
                Assert::false($action->isValidTargetForAbility($world->game, $home)[0], 'not in city');
                Assert::false($action->isValidTargetForAbility($world->game, $pacifist)[0], 'cannot challenge');
            },

            'trigger sets DEFENDING_HONOR challenge type and queues Targeted then transition 01078' => function () {
                $world = new TestWorld();
                [$risk, $action, , $enemy] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $this->trigger($world, $action);

                Assert::same(Game::DEFENDING_HONOR_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'challenge type');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeted');
                Assert::same($enemy->Id, $targeted[0]->targetId, 'target is the enemy performer');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::same(1, $targeted[0]->playerId, 'targeting player is the Risk controller');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01078', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'handed to the enemy player to pick the challenge target');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');

                Assert::same(
                    [EventCharacterTargeted::class, EventTransition::class],
                    array_map(fn($e) => $e::class, $world->theah->queuedEvents),
                    'Targeted before transition so reactions can redirect'
                );
            },

            // WHY: createActionResolvedEvent is raised only when the challenge itself resolves, not here.
            'trigger does NOT queue ActionResolved' => function () {
                $world = new TestWorld();
                [, $action, , $enemy] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'trigger for a different action id does nothing' => function () {
                $world = new TestWorld();
                [, $action, , $enemy] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'challenge type untouched');
            },

            // WHY: the ability target IS the performer. If a reaction (e.g. Vittoria) redirects targeting, the re-queued
            // EventCharacterTargeted carries the new target; CHOSEN_PERFORMER must follow or the challenge states
            // would use the wrong performer / location.
            'redirected EventCharacterTargeted syncs CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                [$risk, $action, , $enemy] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Redirected'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $event = new EventCharacterTargeted();
                $event->playerId = 1;
                $event->targetId = $other->Id;
                $event->sourceId = $risk->Id;
                $event->abilityId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($other->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'synced');
                Assert::count(0, $world->theah->queuedEvents, 'sync queues nothing');
            },

            'canceled EventCharacterTargeted does not change CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                [$risk, $action, , $enemy] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Redirected'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $event = new EventCharacterTargeted();
                $event->targetId = $other->Id;
                $event->sourceId = $risk->Id;
                $event->abilityId = $action->Id;
                $event->canceled = true;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($enemy->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'unchanged');
            },

            'EventCharacterTargeted for another ability does not change CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                [$risk, $action, , $enemy] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $enemy->Id);

                $event = new EventCharacterTargeted();
                $event->targetId = $other->Id;
                $event->sourceId = $risk->Id;
                $event->abilityId = 'someOtherAbility';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same($enemy->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'unchanged');
            },
        ];
    }
}
