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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03002_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03002';
    }

    /**
     * @return array{0:_03002,1:Action_03002,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $aja = $world->placeCharacter(new _03002(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_03002 $action */
        $action = $aja->getActions()[0];
        return [$aja, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a character action that targets characters' => function () {
                $action = new Action_03002();
                Assert::instanceOf(CharacterAction::class, $action, 'character');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            'available when unengaged Aja is in the city with an opposing character' => function () {
                $world = new TestWorld();
                [$aja, $action, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');

                $aja->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $aja->Engaged = false;

                $aja->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $aja->Location = Game::LOCATION_CITY_DOCKS;

                $foe->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled neighbor is not opposing');
            },

            'a Leader is a legal target' => function () {
                $world = new TestWorld();
                [$aja, $action, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'leader counts');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03002, 'highDramaPhase03002');
                Assert::same($aja->Id, $args['performerId'], 'performer');
                Assert::same([$leader->Id], $args['ids'], 'leader id');
            },

            'rejects your own character, an uncontrolled character, and another location' => function () {
                $world = new TestWorld();
                [$aja, $action, $foe] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'foe');
                [$self] = $action->isValidTargetForAbility($world->game, $aja);
                Assert::false($self, 'self');

                $foe->ControllerId = 0;
                [$crowd] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($crowd, 'uncontrolled');
                $foe->ControllerId = 2;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($away, 'location');
            },

            'trigger opens the target chooser' => function () {
                $world = new TestWorld();
                [$aja, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03002', $transitions[0]->transition, 'name');
                Assert::same($aja->Id, $transitions[0]->sourceId, 'source');
            },

            'confirm issues an Aja Combat challenge and does not engage her' => function () {
                $world = new TestWorld();
                [$aja, $action, $foe] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03002, 'highDramaPhase03002', $foe->Id);

                Assert::same($aja->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(Game::AJA_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03002_2', $transitions[0]->transition, 'setup');
                Assert::same($aja->ControllerId, $transitions[0]->playerId, 'controller');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'state');
            },

            'confirm rejects a missing or illegal target' => function () {
                $world = new TestWorld();
                [$aja, $action] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03002, 'x', 999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03002, 'x', $aja->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'self');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },
        ];
    }
}
