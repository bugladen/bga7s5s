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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01172;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01172;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01172_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01172';
    }

    /**
     * Risk in hand; Sorcerer at Docks; target elsewhere.
     *
     * @return array{0:_01172,1:Action_01172,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, array $performerTraits = ['Sorcerer']): array
    {
        $risk = $world->placeCard(new _01172(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', $performerTraits),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01172 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $target];
    }

    public function tests(): array
    {
        return [
            // WHY: pre-commit hook requires ISorcererAbility classes to emit Start + Played events.
            'is a RiskAction Sorcerer ability that targets characters' => function () {
                $action = new Action_01172();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a city Sorcerer and a character elsewhere' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Sorcerer' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = [];
                $performer->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'unavailable when every character shares the Sorcerer location' => function () {
                $world = new TestWorld();
                [, $action, , $target] = $this->scene($world);
                $target->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no elsewhere');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are only city Sorcerers' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $mundane = $world->placeCharacter(new GenericCharacter('Mundane'), Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'Sorcerer only');
                Assert::false(in_array($mundane->Id, $ids, true), 'mundane excluded');
            },

            'trigger queues transition 01172' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01172', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list characters not at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $target] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_BAZAAR, 1);
                $same = $world->placeCharacter(new GenericCharacter('Same'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01172,
                    'highDramaPlayerTurn_01172'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::true(in_array($target->Id, $args['ids'], true), 'target');
                Assert::true(in_array($ally->Id, $args['ids'], true), 'ally elsewhere');
                Assert::false(in_array($same->Id, $args['ids'], true), 'same loc');
                Assert::false(in_array($performer->Id, $args['ids'], true), 'self');
            },

            // WHY: non-Strega performer is wounded first; Start then move then ActionResolved
            // then Played. Start before move so Torsten-style cancel can still see the hook.
            'non-Strega queues wound, Sorcerer Start, move, ActionResolved, Played' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $target] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01172,
                    'highDramaPlayerTurn_01172',
                    $target->Id
                );

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::same(
                    [
                        EventCharacterBeingWounded::class,
                        EventSorcererAbilityStart::class,
                        EventCardMoving::class,
                        EventActionResolved::class,
                        EventSorcererAbilityPlayed::class,
                    ],
                    $order,
                    'event order'
                );

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($performer->Id, $wound->characterId, 'wound performer');
                Assert::same(1, $wound->wounds, 'one wound');
                Assert::same($risk->Id, $wound->sourceId, 'source');
                Assert::same($action->Id, $wound->abilityId, 'ability');

                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0];
                Assert::same($risk->Id, $start->sourceId, 'start source');
                Assert::same($action->Id, $start->abilityId, 'start ability');
                Assert::same($performer->Id, $start->performerId, 'start performer');
                Assert::same($target->Id, $start->targetId, 'start target');
                Assert::same(Game::LOCATION_CITY_FORUM, $start->targetLocation, 'start from-loc');

                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($target->Id, $move->cardId, 'move target');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move->toLocation, 'to performer');
                Assert::false($move->engage, 'no engage');

                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0];
                Assert::same($risk->Id, $played->sourceId, 'played source');
                Assert::same($target->Id, $played->targetId, 'played target');
                Assert::same(Game::LOCATION_CITY_FORUM, $played->targetLocation, 'played from-loc');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'Strega performer skips the wound but still emits Sorcerer Start/Played' => function () {
                $world = new TestWorld();
                [, $action, $performer, $target] = $this->scene($world, ['Sorcerer', 'Strega']);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01172,
                    'highDramaPlayerTurn_01172',
                    $target->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
            },

            'refuses the performer or a same-location character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $same = $world->placeCharacter(new GenericCharacter('Same'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threwSelf = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01172, 'x', $performer->Id);
                } catch (UserException $e) {
                    $threwSelf = true;
                }
                $threwSame = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01172, 'x', $same->Id);
                } catch (UserException $e) {
                    $threwSame = true;
                }
                Assert::true($threwSelf, 'self');
                Assert::true($threwSame, 'same location');
            },
        ];
    }
}
