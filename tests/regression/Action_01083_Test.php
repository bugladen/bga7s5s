<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01083;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01083;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01083_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01083';
    }

    /** @return array{0:_01083,1:Action_01083,2:Character,3:Character} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01083(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Duelist'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Action_01083 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    private function trigger(TestWorld $world, Action_01083 $action): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            // WHY: pre-commit hook keys off "extends RiskCityAction" -> createActionResolvedEvent();
            // 01083 intentionally resolves later (challenge resolution), so lock the base class.
            'is a RiskCityAction that targets characters' => function () {
                $action = new Action_01083();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer selected');
            },

            'available with performer facing an opposing character in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performers');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'unavailable when no opposing character shares the performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01083(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Duelist'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01083 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'opponent elsewhere');
            },

            'an uncontrolled character at the location is not a valid opponent' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01083(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Duelist'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Stray'), Game::LOCATION_CITY_DOCKS, 0);

                /** @var Action_01083 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            // WHY: RiskCityAction — a performer at Player Home is not "in the city".
            'unavailable when the only performer is at Player Home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01083(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Foe Home'), Game::LOCATION_PLAYER_HOME, 2);

                /** @var Action_01083 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            // WHY: Character::canChallenge is overridden by e.g. _01178 / _01190; the Action
            // filters performers through it so those characters cannot be chosen.
            'performer that cannot challenge is excluded' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01083(), Game::LOCATION_HAND, 1);
                $pacifist = new class('Pacifist') extends GenericCharacter {
                    public function canChallenge(\Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah $theah): bool
                    {
                        return false;
                    }
                };
                $world->placeCharacter($pacifist, Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01083 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot challenge');
            },

            'isValidTargetForAbility accepts an opposing character at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
            },

            'isValidTargetForAbility rejects own, distant and uncontrolled characters' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $stray = $world->placeCharacter(new GenericCharacter('Stray'), Game::LOCATION_CITY_DOCKS, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                Assert::false($action->isValidTargetForAbility($world->game, $pal)[0], 'own');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'distant');
                Assert::false($action->isValidTargetForAbility($world->game, $stray)[0], 'uncontrolled');
            },

            'trigger sets Legendary Reputation challenge type and Combat stat, then queues 01083 transition' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $this->trigger($world, $action);

                Assert::same(Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat challenge');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01083', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
            },

            // WHY: the comment in the Action says ActionResolved fires when the challenge is
            // resolved, not at trigger time. Queuing it here would let "After an Action resolves"
            // reactions fire before the challenge even starts.
            'trigger does not queue ActionResolved' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $this->trigger($world, $action);
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'trigger ignores another action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type untouched');
            },

            // WHY: "Only Leaders can intervene" is enforced by Theah::interventionCheck keyed on
            // CHALLENGE_TYPE, not by this Action. Lock that wiring end to end.
            'Legendary Reputation challenge rejects a non-Leader intervener' => function () {
                $world = new TestWorld();
                [, , , $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE);

                $message = '';
                try {
                    $world->theah->interventionCheck($ally);
                } catch (UserException $e) {
                    $message = $e->getMessage();
                }
                Assert::contains('Only Leaders', $message, 'non-Leader blocked');
            },

            'Legendary Reputation challenge allows a Leader to intervene' => function () {
                $world = new TestWorld();
                [, , , $foe] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Their Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE);

                $world->theah->interventionCheck($leader);
                Assert::true(true, 'no exception');
            },

            'a normal challenge still lets a non-Leader intervene' => function () {
                $world = new TestWorld();
                [, , , $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);

                $world->theah->interventionCheck($ally);
                Assert::true(true, 'no exception');
            },
        ];
    }
}
