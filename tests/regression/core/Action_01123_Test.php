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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01123_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01123';
    }

    /**
     * Valeri at Docks; enemy at Forum (adjacent in 2-player map).
     *
     * @return array{0:_01123,1:GenericCharacter,2:Action_01123}
     */
    private function scene(TestWorld $world): array
    {
        $valeri = $world->placeCharacter(new _01123(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_01123 $action */
        $action = $valeri->getActions()[0];
        return [$valeri, $foe, $action];
    }

    private function act(TestWorld $world, Action_01123 $action, int $id): void
    {
        $action->actFromActionWithId(
            $world->game,
            States::HIGH_DRAMA_PLAYER_TURN_01123,
            'highDramaPlayerTurn_01123',
            $id
        );
    }

    private function actThrows(TestWorld $world, Action_01123 $action, int $id): bool
    {
        try {
            $this->act($world, $action, $id);
        } catch (UserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            // WHY: IAbilityThatTargetsCharacters — Dabney / other cancel paths key off CharacterTargeted.
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_01123();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when an enemy is at an adjacent city location' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent foe');
            },

            'unavailable when Valeri is engaged' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);
                $valeri->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when the only enemy is at the same location' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location');
            },

            'unavailable when the only enemy is not adjacent' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_BAZAAR;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not adjacent');
            },

            'an uncontrolled character does not enable the action' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $foe->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'unavailable once used or blanked' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
                $action->Used = false;
                $valeri->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues the 01123 chooser transition' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01123', $transitions[0]->transition, 'name');
                Assert::same($valeri->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action id');
            },

            'args list adjacent enemy character ids' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01123,
                    'highDramaPlayerTurn_01123'
                );

                Assert::true(in_array($foe->Id, $args['ids'], true), 'adjacent foe');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far excluded');
            },

            // WHY: createActionResolvedEvent not needed — this action starts a challenge.
            'choosing a target moves Valeri, stamps challenge globals, and does not resolve the action' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $action] = $this->scene($world);

                $this->act($world, $action, $foe->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($valeri->Id, $moves[0]->cardId, 'Valeri moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to foe');
                Assert::true($moves[0]->engage, 'engage on move');

                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat');
                Assert::same(Game::VALERI_MIKHAILOV_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'no intervene');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, '01123_2');
                Assert::same('01123_2', $transitions[0]->transition, 'challenge follow-up');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same(['opponentChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'choosing an invalid target throws' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);

                Assert::true($this->actThrows($world, $action, $ally->Id), 'own character');
                Assert::true($this->actThrows($world, $action, $far->Id), 'not adjacent');
            },
        ];
    }
}
