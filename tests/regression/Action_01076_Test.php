<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01076_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01076';
    }

    /** @return array{0:_01076,1:Action_01076,2:Character} blood mark in hand, its action, a Sorcerer at Docks */
    private function scene(TestWorld $world): array
    {
        $mark = $world->placeCard(new _01076(), Game::LOCATION_HAND, 1);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01076 $action */
        $action = $mark->getActions()[0];
        return [$mark, $action, $sorcerer];
    }

    public function tests(): array
    {
        return [
            // WHY: pre-commit hook requires ISorcererAbility classes to emit Start + Played events.
            'is a Sorcerer ability' => function () {
                Assert::instanceOf(ISorcererAbility::class, new Action_01076(), 'ISorcererAbility');
            },

            'available with a Sorcerer in play and the Risk in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'available when the only Sorcerer is at Player Home (in play, not in city)' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $sorcerer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home counts as in play');
            },

            'unavailable without a Sorcerer' => function () {
                $world = new TestWorld();
                $mark = $world->placeCard(new _01076(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($mark->getActions()[0]->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'unavailable when the only Sorcerer belongs to the opponent' => function () {
                $world = new TestWorld();
                $mark = $world->placeCard(new _01076(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Theirs', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($mark->getActions()[0]->isAvailableToPlayer(1, $world->theah), 'opposing Sorcerer');
            },

            'unavailable when the Risk is not in hand (unless override)' => function () {
                $world = new TestWorld();
                [$mark, $action] = $this->scene($world);
                $mark->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },

            'performers are Sorcerers only' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Theirs', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);

                $performers = $action->getPerformersForAction(1, $world->theah);

                Assert::count(1, $performers, 'one performer');
                Assert::same($sorcerer->Id, array_values($performers)[0]->Id, 'the Sorcerer');
            },

            'trigger queues transition 01076 and nothing else' => function () {
                $world = new TestWorld();
                [$mark, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01076', $transitions[0]->transition, 'name');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($mark->Id, $transitions[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedEvents, 'only the transition');
            },

            'trigger for a different action id does nothing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'step 1 args list every city location except the performer\'s own' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076, 'highDramaPlayerTurn_01076');

                Assert::same($sorcerer->Id, $args['performerId'], 'performer');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'own location excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'Bazaar (any city location, not just adjacent)');
                Assert::count(4, $args['locationIds'], '5 city locations minus own');
            },

            'step 1 location choice stores CHOSEN_LOCATION and goes to locationChosen' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076, 'x', [Game::LOCATION_CITY_FORUM]);

                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named transition');
                Assert::count(0, $world->theah->queuedEvents, 'no events yet');
            },

            'step 2 args offer other own characters at the performer\'s location only' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076_2, 'x');

                Assert::same([$ally->Id], $args['characterIds'], 'ally only (not performer, foe or remote)');
                Assert::same($sorcerer->Id, $args['performerId'], 'performer');
            },

            'step 2 with a companion wounds the performer, moves both, and ends the Sorcerer ability' => function () {
                $world = new TestWorld();
                [$mark, $action, $sorcerer] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076_2, 'x', $ally->Id);

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0];
                Assert::same($action->Id, $start->abilityId, 'start ability');
                Assert::same($sorcerer->Id, $start->performerId, 'start performer');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound');
                Assert::same($sorcerer->Id, $wounds[0]->characterId, 'performer is wounded (not the companion)');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($mark->Id, $wounds[0]->sourceId, 'source');
                Assert::same($action->Id, $wounds[0]->abilityId, 'ability');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'performer + companion move');
                Assert::same($sorcerer->Id, $moves[0]->cardId, 'performer first');
                Assert::same($ally->Id, $moves[1]->cardId, 'companion second');
                foreach ($moves as $move) {
                    Assert::same(Game::LOCATION_CITY_DOCKS, $move->fromLocation, 'from');
                    Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to');
                    Assert::false($move->engage, 'ability move does not engage');
                    Assert::same($action->Id, $move->abilityId, 'ability id');
                }

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'action resolved');
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::count(1, $played, 'played');
                Assert::same($action->Id, $played[0]->abilityId, 'played ability');
                Assert::same($sorcerer->Id, $played[0]->performerId, 'played performer');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            // WHY: "Then, you may wound them. If you do, move another character" - id 0 is the decline path:
            // performer still moves, but no wound is dealt and no companion is moved.
            'step 2 with id 0 moves only the performer and wounds nobody' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_BAZAAR);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076_2, 'x', 0);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'performer only');
                Assert::same($sorcerer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'destination');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start still queued');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played still queued');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'action still resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            'step 2 event order: Start precedes wound and moves; Played comes last' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01076_2, 'x', $ally->Id);

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::same([
                    EventSorcererAbilityStart::class,
                    EventCharacterBeingWounded::class,
                    EventCardMoving::class,
                    EventCardMoving::class,
                    EventActionResolved::class,
                    EventSorcererAbilityPlayed::class,
                ], $order, 'queue order');
            },
        ];
    }
}
