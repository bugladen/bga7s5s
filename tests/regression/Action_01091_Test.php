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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01091_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01091';
    }

    /**
     * Dolores (P1) at Docks with a wounded ally and a wounded opposing character there, plus a wounded far-away character.
     *
     * @return array{0:_01091,1:Action_01091,2:Character,3:Character}
     */
    private function scene(TestWorld $world): array
    {
        $dolores = $world->placeCharacter(new _01091(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $ally->Wounds = 1;
        $foe->Wounds = 2;
        /** @var Action_01091 $action */
        $action = $dolores->getActions()[0];
        return [$dolores, $action, $ally, $foe];
    }

    public function tests(): array
    {
        return [
            'available when a character at her location is wounded' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'opposing wounded characters count' => function () {
                $world = new TestWorld();
                [, $action, $ally] = $this->scene($world);
                $ally->Wounds = 0;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'foe still wounded');
            },

            'Dolores can heal herself' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally, $foe] = $this->scene($world);
                $ally->Wounds = 0;
                $foe->Wounds = 0;
                $dolores->Wounds = 1;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'self wounded');
            },

            'unavailable when nobody at her location is wounded' => function () {
                $world = new TestWorld();
                [, $action, $ally, $foe] = $this->scene($world);
                $ally->Wounds = 0;
                $foe->Wounds = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no wounds');
            },

            'wounded characters at other locations do not count' => function () {
                $world = new TestWorld();
                [, $action, $ally, $foe] = $this->scene($world);
                $ally->Wounds = 0;
                $foe->Wounds = 0;
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $far->Wounds = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'other location');
            },

            // WHY: City Action - Dolores must be in the city (Home has no location-mates to heal).
            'unavailable when Dolores is at Home' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally] = $this->scene($world);
                $dolores->Location = Game::LOCATION_PLAYER_HOME;
                $ally->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Dolores' => function () {
                $world = new TestWorld();
                [$dolores, $action] = $this->scene($world);
                $dolores->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01091' => function () {
                $world = new TestWorld();
                [$dolores, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01091', $transitions[0]->transition, 'transition name');
                Assert::same($dolores->Id, $transitions[0]->sourceId, 'source');
            },

            'step 1 args list wounded characters at her location only' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally, $foe] = $this->scene($world);
                $unhurt = $world->placeCharacter(new GenericCharacter('Unhurt'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $far->Wounds = 3;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'highDramaPlayerTurn_01091');

                Assert::same($dolores->Id, $args['performerId'], 'performer');
                Assert::same([$ally->Id, $foe->Id], $args['ids'], 'wounded at location');
                Assert::false(in_array($unhurt->Id, $args['ids'], true), 'unhurt excluded');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far excluded');
            },

            'step 2 args echo the chosen targets' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, [$ally->Id, $foe->Id]);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091_2, 'highDramaPlayerTurn_01091_2');

                Assert::same($dolores->Id, $args['performerId'], 'performer');
                Assert::same([$ally->Id, $foe->Id], $args['ids'], 'chosen targets');
            },

            'isValidTargetForAbility accepts a wounded character at her location' => function () {
                $world = new TestWorld();
                [, $action, $ally] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::true($ok, 'valid');
            },

            'isValidTargetForAbility rejects another location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $far->Wounds = 1;
                [$ok] = $action->isValidTargetForAbility($world->game, $far);
                Assert::false($ok, 'other location');
            },

            'isValidTargetForAbility rejects an unwounded character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $unhurt = $world->placeCharacter(new GenericCharacter('Unhurt'), Game::LOCATION_CITY_DOCKS, 1);
                [$ok] = $action->isValidTargetForAbility($world->game, $unhurt);
                Assert::false($ok, 'unwounded');
            },

            'step 1 with one target heals one wound and resolves' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally] = $this->scene($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [$ally->Id]);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'one heal');
                Assert::same($ally->Id, $heals[0]->characterId, 'target');
                Assert::same(1, $heals[0]->wounds, 'one wound');
                Assert::same($dolores->Id, $heals[0]->sourceId, 'source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability id');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'step 1 can heal an opposing character' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [$foe->Id]);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::same($foe->Id, $heals[0]->characterId, 'foe healed');
            },

            // WHY: two targets cost a discard, so step 1 only stores them and routes to the discard-choice state.
            'step 1 with two targets stores them and asks for a discard' => function () {
                $world = new TestWorld();
                [, $action, $ally, $foe] = $this->scene($world);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [$ally->Id, $foe->Id]);

                Assert::same([$ally->Id, $foe->Id], $world->game->globals->get(Game::CHOSEN_TARGET), 'stored');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued yet');
                Assert::same(['charactersChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'step 1 refuses an unknown character id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [999999]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid id');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'step 1 refuses an unwounded target even when another target is valid' => function () {
                $world = new TestWorld();
                [, $action, $ally] = $this->scene($world);
                $unhurt = $world->placeCharacter(new GenericCharacter('Unhurt'), Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [$ally->Id, $unhurt->Id]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'unwounded');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 1 refuses a target at another location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $far->Wounds = 1;

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091, 'x', [$far->Id]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'other location');
            },

            'step 2 discards the chosen card and heals every stored target in one batch' => function () {
                $world = new TestWorld();
                [$dolores, $action, $ally, $foe] = $this->scene($world);
                $hand = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::CHOSEN_TARGET, [$ally->Id, $foe->Id]);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091_2, 'x', $hand->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'one discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'discarded');
                Assert::same($dolores->Id, $discards[0]->sourceId, 'source');
                Assert::false($discards[0]->AsPayment, 'not payment');
                Assert::false($discards[0]->asEffect, 'not effect');

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(2, $heals, 'two heals');
                Assert::same([$ally->Id, $foe->Id], array_map(fn($h) => $h->characterId, $heals), 'both targets');
                Assert::same(1, $heals[0]->wounds, 'one wound each');
                Assert::same(1, $heals[1]->wounds, 'one wound each');
                Assert::same($heals[0]->batchId, $heals[1]->batchId, 'same batch');
                Assert::true($heals[0]->batchId > 0, 'real batch id');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'step 2 refuses a card that is not in hand' => function () {
                $world = new TestWorld();
                [, $action, $ally, $foe] = $this->scene($world);
                $stray = $world->placeCharacter(new GenericCharacter('Stray'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_TARGET, [$ally->Id, $foe->Id]);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091_2, 'x', $stray->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'step 2 refuses an opposing player\'s hand card' => function () {
                $world = new TestWorld();
                [, $action, $ally, $foe] = $this->scene($world);
                $theirs = $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_HAND, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, [$ally->Id, $foe->Id]);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01091_2, 'x', $theirs->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'wrong hand');
            },
        ];
    }
}
