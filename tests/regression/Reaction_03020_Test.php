<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03020_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03020';
    }

    /**
     * @return array{0:_03020,1:Reaction_03020,2:GenericLeader}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03020(), Game::LOCATION_HAND, 1);
        $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->leadersByPlayerId[1] = $leader;
        /** @var Reaction_03020 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $leader];
    }

    private function moving(TestWorld $world, string $from, int $batchId = 4): EventRenownMovingBetweenLocations
    {
        $event = new EventRenownMovingBetweenLocations();
        $event->theah = $world->theah;
        $event->fromLocation = $from;
        $event->toLocation = Game::LOCATION_CITY_FORUM;
        $event->amount = 2;
        $event->batchId = $batchId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a cancelling risk reaction' => function () {
                $reaction = new Reaction_03020();
                Assert::instanceOf(RiskReaction::class, $reaction, 'risk reaction');
                Assert::instanceOf(ICancelReaction::class, $reaction, 'cancel');
            },

            // WHY: The moving event is informational. The Renown actually changes on the
            // add and remove events that share its batch, and those are deleted only
            // after the risk is paid. Decline leaves them in place.
            'cancel pays first, then deletes the renown batch' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $leader] = $this->scene($world);
                $removed = new EventRenownRemovedFromLocation();
                $removed->batchId = 4;
                $removed->location = $leader->Location;
                $added = new EventRenownAddedToLocation();
                $added->batchId = 4;
                $added->location = Game::LOCATION_CITY_FORUM;
                $world->theah->queueEvent($removed);
                $world->theah->queueEvent($added);

                $reaction->handleEvent($this->moving($world, $leader->Location));
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['cancel', 'decline'], $buttons, 'buttons');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class)[0];
                Assert::same($risk->Id, $pay->cardId, 'paying for the risk');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay->payStateType, 'in-hand reaction');
                Assert::false($reaction->Used, 'not spent until the trigger');

                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'remove deleted');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'add deleted');
                Assert::true($reaction->Used, 'spent');
            },

            'decline does not pay, and a move from somewhere else is ignored' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $leader] = $this->scene($world);
                $reaction->handleEvent($this->moving($world, Game::LOCATION_CITY_BAZAAR));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not the leader location');

                $reaction->handleEvent($this->moving($world, $leader->Location));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'decline');

                $risk->Location = 'Discard-1';
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($this->moving($world, $leader->Location));
                Assert::count(0, $world->theah->queuedEvents, 'risk not in hand');
            },
        ];
    }
}
