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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01133_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01133';
    }

    /**
     * @return array{0:_01133,1:Action_01133,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01133(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01133 $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$risk, $action, $performer, $ally];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction Sorcerer ability that targets characters' => function () {
                $action = new Action_01133();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a Sorcerer in play (including Home)' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city');
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'Home Sorcerer ok');
            },

            'unavailable without a Sorcerer' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = [];
                $performer->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'trigger queues the 01133 character chooser' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01133', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'state _1 args list controlled characters at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $ally] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Else'), Game::LOCATION_CITY_FORUM, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01133,
                    'highDramaPlayerTurn_01133'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::true(in_array($performer->Id, $args['ids'], true), 'self');
                Assert::true(in_array($ally->Id, $args['ids'], true), 'ally');
                Assert::false(in_array($foe->Id, $args['ids'], true), 'foe excluded');
                Assert::false(in_array($elsewhere->Id, $args['ids'], true), 'elsewhere excluded');
            },

            'choosing a valid character stores CHOSEN_TARGET and transitions' => function () {
                $world = new TestWorld();
                [, $action, , $ally] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01133,
                    'highDramaPlayerTurn_01133',
                    $ally->Id
                );

                Assert::same($ally->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'next');
            },

            'choosing an enemy or wrong-location character throws' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);

                $threwFoe = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01133,
                        'x',
                        $foe->Id
                    );
                } catch (UserException $e) {
                    $threwFoe = true;
                }
                $threwFar = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01133,
                        'x',
                        $far->Id
                    );
                } catch (UserException $e) {
                    $threwFar = true;
                }
                Assert::true($threwFoe, 'foe');
                Assert::true($threwFar, 'far');
            },

            'state _2 args list other city locations plus Home when not already Home' => function () {
                $world = new TestWorld();
                [, $action, $performer, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $ally->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01133_2,
                    'highDramaPlayerTurn_01133_2'
                );

                Assert::same($ally->Id, $args['characterId'], 'character');
                Assert::false(in_array($performer->Location, $args['locationIds'], true), 'current excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'Home');
            },

            'choosing a location queues Sorcerer start and 01133_3 transition' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $ally->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01133_2,
                    'highDramaPlayerTurn_01133_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, '01133_3');
                Assert::same('01133_3', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'next');
            },

            'state _3 moves the target, resolves the Action, and plays Sorcerer' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01133_3,
                    'highDramaPlayerTurn_01133_3'
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($ally->Id, $moves[0]->cardId, 'ally');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            // WHY: optional engage sets WillEngage; discount equals printed WealthCost and is
            // gated on this Action id so sticky WillEngage cannot discount unrelated hand Actions.
            'WillEngage discounts this Action by WealthCost' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $risk->WillEngage = true;
                $explanations = [];
                $discount = $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations);
                Assert::same(1, $discount, 'WealthCost');
                Assert::count(1, $explanations, 'explained');

                $other = new Action_01133();
                $otherExplanations = [];
                Assert::same(
                    0,
                    $action->getActionFromHandDiscount($world->theah, $performer, $other, $otherExplanations),
                    'other Action id'
                );
            },
        ];
    }
}
