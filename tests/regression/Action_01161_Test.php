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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01161_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01161';
    }

    /**
     * Boon in hand; en garde Sorcerer at Docks.
     *
     * @return array{0:_01161,1:Action_01161,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01161(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $performer->Engaged = false;
        /** @var Action_01161 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction Sorcerer ability that targets characters' => function () {
                $action = new Action_01161();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'needs performer');
            },

            'available with an en garde city Sorcerer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the only Sorcerer is engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable without a Sorcerer in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = [];
                $performer->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'unavailable when the only Sorcerer is at Home' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are only en garde city Sorcerers' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged = $world->placeCharacter(
                    new GenericCharacter('Tired', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $engaged->Engaged = true;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'only ready Sorcerer');
                Assert::false(in_array($plain->Id, $ids, true), 'plain excluded');
                Assert::false(in_array($engaged->Id, $ids, true), 'engaged excluded');
            },

            'trigger queues transition 01161' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01161', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list every character at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01161,
                    'highDramaPlayerTurn_01161'
                );

                Assert::true(in_array($performer->Id, $args['ids'], true), 'self ok');
                Assert::true(in_array($foe->Id, $args['ids'], true), 'foe ok');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far excluded');
            },

            'act step 1 engages performer, starts sorcery, and queues 01161_2' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01161,
                    'highDramaPlayerTurn_01161',
                    $target->Id
                );

                Assert::same($target->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'chosen');
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::same('01161_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'next');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
            },

            'act step 1 refuses a character at a different location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01161,
                        'x',
                        $far->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            // WHY (pre-commit ISorcererAbility): stateFromAction must fire Played + ActionResolved
            // after createRiskAttachment — Start alone is not enough for the hook.
            'state 01161_2 creates Boon attachment and fires Played + ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $target->Id);

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01161_2,
                    'highDramaPlayerTurn_01161_2'
                );

                Assert::count(1, $world->game->createdRiskAttachments, 'createRiskAttachment');
                Assert::same('01161_Boon', $world->game->createdRiskAttachments[0]['className'], 'class');
                Assert::same($risk->Id, $world->game->createdRiskAttachments[0]['originalCardId'], 'original');
                Assert::same($target->Id, $world->game->createdRiskAttachments[0]['targetId'], 'target');
                Assert::same($action->Id, $world->game->createdRiskAttachments[0]['abilityId'], 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'state constants registered' => function () {
                Assert::same(401161, States::HIGH_DRAMA_PLAYER_TURN_01161, '01161');
                Assert::same(4011612, States::HIGH_DRAMA_PLAYER_TURN_01161_2, '01161_2');
            },
        ];
    }
}
