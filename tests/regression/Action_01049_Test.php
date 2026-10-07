<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01049_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01049';
    }

    private function equipFlint(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
        $flint->AttachedToId = $host->Id;
        $host->Attachments[] = $flint->Id;
        return [$host, $flint];
    }

    public function tests(): array
    {
        return [
            'available with opposing character at location' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without opposing character' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'trigger queues transition 01049' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01049', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY: already-engaged target skips choice — wound + engage flintlock immediately
            'selecting engaged target wounds and engages flintlock' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049,
                    'highDramaPhase01049',
                    $foe->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage flint');
            },

            'selecting engarde target queues opponent choice transition' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049,
                    'highDramaPhase01049',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same('01049_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');
            },

            'opponent chooses engage' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049_2,
                    'highDramaPhase01049_2',
                    1
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage foe');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'opponent refuses — wound and engage flintlock' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                /** @var Action_01049 $action */
                $action = $flint->getActions()[0];
                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01049_2,
                    'highDramaPhase01049_2',
                    2
                );

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage flint');
            },
        ];
    }
}
