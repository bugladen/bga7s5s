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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02023_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02023';
    }

    /**
     * Jack at Docks (Renown 2); Forum Renown 0; opposing non-Leader at Docks.
     *
     * @return array{0:_02023,1:Action_02023,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $jack = $world->placeCharacter(new _02023(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);
        /** @var Action_02023 $action */
        $action = $jack->getActions()[0];
        return [$jack, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_02023();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            // WHY: City + opposing non-Leader + adjacent city with strictly less Renown.
            'available with opposing non-Leader and lower-Renown adjacent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [$jack, $action] = $this->scene($world);
                $jack->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable without opposing non-Leader' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _02023(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericLeader('Enemy Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_02023 $action */
                $action = $jack->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader only');
            },

            'unavailable when no adjacent has strictly less Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'equal');
            },

            'isValidTargetForAbility rejects Leaders, own, and wrong location' => function () {
                $world = new TestWorld();
                [$jack, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $jack->Id);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'foe');
                [$ok,] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($ok, 'leader');
                [$ok,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ok, 'own');
                [$ok,] = $action->isValidTargetForAbility($world->game, $far);
                Assert::false($ok, 'wrong location');
            },

            'trigger queues transition 02023' => function () {
                $world = new TestWorld();
                [$jack, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02023', $transitions[0]->transition, 'name');
                Assert::same($jack->Id, $transitions[0]->sourceId, 'source');
            },

            'act character stores target and transitions characterChosen' => function () {
                $world = new TestWorld();
                [$jack, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $jack->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02023,
                    'highDramaPlayerTurn_02023',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named');
            },

            'act character rejects Leaders' => function () {
                $world = new TestWorld();
                [$jack, $action] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $jack->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02023,
                        'highDramaPlayerTurn_02023',
                        $leader->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            // WHY: move with engage=false + ActionResolved → locationChosen.
            'act location moves target without engage and resolves' => function () {
                $world = new TestWorld();
                [$jack, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $jack->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02023_2,
                    'highDramaPlayerTurn_02023_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            'act location rejects equal-or-greater Renown destination' => function () {
                $world = new TestWorld();
                [$jack, $action, $foe] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $jack->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02023_2,
                        'highDramaPlayerTurn_02023_2',
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
