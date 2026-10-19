<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03072;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03072_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03072';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03072();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: The target needs a real attachment. Engaged ones are destroyed. The rest are
            // engaged. Fake attachments do not qualify. The snapshot is taken before the queue runs.
            'destroys engaged attachments and engages the rest' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03072(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03072 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no attachment');

                $engaged = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->AttachedToId = $foe->Id;
                $engaged->Engaged = true;
                $ready = $world->placeCard(new _03065(), Game::LOCATION_CITY_DOCKS, 2);
                $ready->AttachedToId = $foe->Id;
                $fake = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 2);
                $fake->FakeAttachment = true;
                $fake->AttachedToId = $foe->Id;
                $foe->Attachments = [$engaged->Id, $ready->Id, $fake->Id];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'has attachments');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03072', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03072, 'x', $foe->Id);
                Assert::same($engaged->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0];
                Assert::same($engaged->Id, $discard->cardId, 'destroyed');
                Assert::true($discard->asEffect, 'effect');
                Assert::same([$ready->Id], array_map(fn ($e) => $e->cardId, $world->theah->queuedOfType(EventCardEngaged::class)), 'engage the ready one');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
