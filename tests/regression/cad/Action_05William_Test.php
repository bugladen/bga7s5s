<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05William;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_05William_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05William';
    }

    /**
     * Scheme at Home; performer with an equipped Artifact and an opposing non-Leader at Docks.
     *
     * @return array{0:_05William,1:Action_05William,2:GenericCharacter,3:GenericCharacter,4:_01204}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _05William(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $artifact = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 1);
        $artifact->AttachedToId = $performer->Id;
        $performer->Attachments[] = $artifact->Id;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $world->game->globals->set(Game::CHOSEN_CARD, $artifact->Id);
        /** @var Action_05William $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer, $foe, $artifact];
    }

    public function tests(): array
    {
        return [
            // WHY: pick queues ONLY targeting. The Artifact Engage is the "•" cost but is gated with the
            // hook (Action_01015 seat) so a canceled targeting does not leave it engaged for nothing.
            'target pick queues CharacterTargeted only; survive engages Artifact, moves Home, resolves' => function () {
                $world = new TestWorld();
                [$scheme, $action, , $foe, $artifact] = $this->scene($world);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05WILLIAM_2, 'x', $foe->Id);

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'hook target');
                Assert::same($action->Id, $targeted[0]->abilityId, 'hook ability');
                Assert::true($targeted[0]->batchId !== null, 'batch');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move beside pick');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved beside pick');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($artifact->Id, $engages[0]->cardId, 'artifact engaged');
                Assert::same($hook->batchId, $engages[0]->batchId, 'engage shares batch');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::false($moves[0]->engage, 'move does not engage');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'source');
                Assert::same($hook->batchId, $moves[0]->batchId, 'move shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: Cloud Bottle / UL cancel the hook — Artifact stays ready, nothing moves.
            'canceled CharacterTargeted does not engage, move or resolve' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = $action->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'CharacterTargeted for another ability is ignored' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $hook = new EventCharacterTargeted();
                $hook->targetId = $foe->Id;
                $hook->abilityId = 'someone-else';
                $hook->theah = $world->theah;
                $action->handleEvent($hook);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
