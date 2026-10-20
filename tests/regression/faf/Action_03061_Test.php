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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03061_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03061';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_03061();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Hero is a trait gate. The target is an en garde non-Leader at Home.
            // Engaged means not en garde. The move does not engage.
            'a hero moves an en garde non-leader from home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03061(), Game::LOCATION_PLAYER_HOME, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_FORUM, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_PLAYER_HOME, 2);
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_03061 $action */
                $action = $scheme->getActions()[0];
                Assert::same([$hero->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'hero');
                unset($plain);

                $target->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged target');
                $target->Engaged = false;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'en garde');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $hero->Id);
                [$valid] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($valid, 'leader');
                [$valid] = $action->isValidTargetForAbility($world->game, $target);
                Assert::true($valid, 'target');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03061', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::same($action->Id, $world->theah->queuedOfType(EventTransition::class)[0]->internalId, 'action path');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03061, 'x', $leader->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'leader rejected');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03061, 'x', $target->Id);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $move->toLocation, 'docks');
                Assert::false($move->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([''], array_slice($world->game->gamestate->transitions, -1), 'empty transition');
            },
        ];
    }
}
