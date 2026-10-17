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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03001_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03001';
    }

    /**
     * @return array{0:_03001,1:Action_03001,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $cesca = $world->placeCharacter(new _03001(), Game::LOCATION_CITY_DOCKS, 1);
        $strega = $world->placeCharacter(new GenericCharacter('Strand', ['Strega']), Game::LOCATION_CITY_DOCKS, 1);
        $strega->Wounds = 1;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_03001 $action */
        $action = $cesca->getActions()[0];
        return [$cesca, $action, $strega, $foe];
    }

    public function tests(): array
    {
        return [
            'is a character action that targets characters' => function () {
                $action = new Action_03001();
                Assert::instanceOf(CharacterAction::class, $action, 'character action');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::false($action->RequiresPerformerSelected, 'Cesca is the performer');
            },

            'available in the city with a wounded Strega and an opposing non-Leader who can be wounded' => function () {
                $world = new TestWorld();
                [$cesca, $action, $strega] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');

                $cesca->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engage is not a cost');
                $cesca->Engaged = false;

                $cesca->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $cesca->Location = Game::LOCATION_CITY_DOCKS;

                $strega->Wounds = 0;
                $cesca->Wounds = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no wound to move');
            },

            'a Leader or an immune character does not open the action' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                $immune = new class('Ward') extends GenericCharacter {
                    public function canBeWoundedByOpponentAbilities(): bool
                    {
                        return false;
                    }
                };
                $world->placeCharacter($immune, Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody legal');
            },

            'trigger opens the Strega chooser' => function () {
                $world = new TestWorld();
                [$cesca, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03001', $transitions[0]->transition, 'name');
                Assert::same($cesca->Id, $transitions[0]->sourceId, 'source');
            },

            'first args are wounded Strega at Cesca location, including Cesca' => function () {
                $world = new TestWorld();
                [$cesca, $action, $strega] = $this->scene($world);
                $cesca->Wounds = 2;
                $elsewhere = $world->placeCharacter(new GenericCharacter('Away', ['Strega']), Game::LOCATION_CITY_FORUM, 1);
                $elsewhere->Wounds = 1;
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy', ['Strega']), Game::LOCATION_CITY_DOCKS, 1);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'highDramaPhase03001');
                Assert::same($cesca->Id, $args['performerId'], 'performer');
                Assert::true(in_array($cesca->Id, $args['ids'], true), 'Cesca herself');
                Assert::true(in_array($strega->Id, $args['ids'], true), 'wounded neighbor');
                Assert::false(in_array($elsewhere->Id, $args['ids'], true), 'other location');
                Assert::false(in_array($healthy->Id, $args['ids'], true), 'unwounded');
            },

            'choosing a Strega stores the source and does not heal yet' => function () {
                $world = new TestWorld();
                [, $action, $strega] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'highDramaPhase03001', $strega->Id);
                Assert::same($strega->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'source');
                Assert::same(['stregaChosen'], $world->game->gamestate->transitions, 'transition');
                Assert::count(0, $world->theah->queuedEvents, 'no heal yet');
            },

            'Strega pick rejects a non-Strega, the wrong controller, another location, and no wound' => function () {
                $world = new TestWorld();
                [$cesca, $action, $strega] = $this->scene($world);
                $brute = $world->placeCharacter(new GenericCharacter('Bruiser'), Game::LOCATION_CITY_DOCKS, 1);
                $brute->Wounds = 1;
                $this->expectUser(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'x', $brute->Id), 'trait');

                $strega->ControllerId = 2;
                $this->expectUser(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'x', $strega->Id), 'controller');
                $strega->ControllerId = 1;

                $strega->Location = Game::LOCATION_CITY_FORUM;
                $this->expectUser(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'x', $strega->Id), 'location');
                $strega->Location = $cesca->Location;

                $strega->Wounds = 0;
                $this->expectUser(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001, 'x', $strega->Id), 'no wound');
            },

            // WHY: Args use isNotControlledByPlayer, which drops uncontrolled characters.
            // isValidTarget only rejects your own controller, so an uncontrolled click still passes.
            'second step lists opposing non-Leaders; an uncontrolled neighbor is a legal click but not a button' => function () {
                $world = new TestWorld();
                [$cesca, $action, $strega, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $strega->Id);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                $crowd = $world->placeCharacter(new GenericCharacter('Crowd'), Game::LOCATION_CITY_DOCKS, 2);
                $crowd->ControllerId = 0;
                $friend = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001_2, 'highDramaPhase03001_2');
                Assert::same([$foe->Id], $args['ids'], 'only the foe');
                Assert::same($strega->Id, $args['sourceId'], 'source');
                Assert::same($cesca->Id, $args['performerId'], 'performer');

                [$crowdOk] = $action->isValidTargetForAbility($world->game, $crowd);
                Assert::true($crowdOk, 'uncontrolled click');
                [$leaderOk] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($leaderOk, 'leader');
                [$friendOk] = $action->isValidTargetForAbility($world->game, $friend);
                Assert::false($friendOk, 'friend');
            },

            'confirm heals the Strega, wounds the target, and resolves the action' => function () {
                $world = new TestWorld();
                [$cesca, $action, $strega, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $strega->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001_2, 'highDramaPhase03001_2', $foe->Id);

                $heal = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::same($strega->Id, $heal[0]->characterId, 'heal source');
                Assert::same(1, $heal[0]->wounds, 'one wound');
                Assert::same($action->Id, $heal[0]->abilityId, 'heal ability');
                Assert::same($foe->Id, $wound[0]->characterId, 'wound target');
                Assert::same($cesca->Id, $wound[0]->sourceId, 'wound source');
                Assert::same(1, $resolved[0]->playerId, 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'confirm rejects a Strega who lost the wound or left the location' => function () {
                $world = new TestWorld();
                [, $action, $strega, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $strega->Id);
                $strega->Wounds = 0;
                $this->expectUser(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03001_2, 'x', $foe->Id), 'source');
            },
        ];
    }

    private function expectUser(callable $call, string $message): void
    {
        $threw = false;
        try {
            $call();
        } catch (UserException) {
            $threw = true;
        }
        Assert::true($threw, $message);
    }
}
