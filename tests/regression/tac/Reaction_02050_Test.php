<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02050;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02050_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02050';
    }

    /**
     * @return array{0:_02050,1:Reaction_02050,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02050(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Diplomat'), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_02050 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $performer];
    }

    private function renown(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): EventRenownAddedToLocation
    {
        $event = new EventRenownAddedToLocation();
        $event->location = $location;
        $event->amount = 1;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02050(), 'RiskReaction');
            },

            // A character at a different city location who can pressure with Influence.
            'Renown added anywhere offers a performer at another location' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $same = $world->placeCharacter(new GenericCharacter('Dockhand'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->renown($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::true(str_contains($reaction->getReactionDescription($world->theah), Game::LOCATION_CITY_DOCKS), 'from');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['pressure-' . $performer->Id, 'decline'], $ids, 'forum only');
                Assert::false(in_array('pressure-' . $same->Id, $ids, true), 'same location excluded');
            },

            'does not offer from home, with only dashed Influence, when not in hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->renown($world));
                Assert::count(0, $world->theah->queuedEvents, 'home is not a city performer');

                $performer->Location = Game::LOCATION_CITY_FORUM;
                $world->placeCharacter(new _02043(), Game::LOCATION_CITY_BAZAAR, 1);
                $performer->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($this->renown($world, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'Miyato cannot pressure Influence and the other character is at the Renown');

                $performer->Location = Game::LOCATION_CITY_FORUM;
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->renown($world));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($this->renown($world));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'choosing a performer enters the in-hand reaction pay state' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure-' . $performer->Id);

                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'type');
                Assert::same($reaction->Id, $pay[0]->internalId, 'reaction');
                Assert::false($reaction->Used, 'not used until paid');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'decline does not enter pay' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::false(str_contains($reaction->getReactionDescription($world->theah), Game::LOCATION_CITY_DOCKS), 'location cleared');
            },

            // WHY: PRESSURE_TYPE is assigned, not OR'd, so a flag already on the global is replaced.
            'paying pressures the performer location with Influence' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure-' . $performer->Id);
                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::CAPTAINS_COAT_PRESSURE_TYPE);

                $event = new EventRiskReactionTriggered();
                $event->internalId = $reaction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->Used, 'used');
                Assert::same(Game::USSURAN_INTRIGUE_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'replaced');
                Assert::same($performer->Id, $world->game->pressureLocationCalls[0]['performerId'], 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->pressureLocationCalls[0]['location'], 'forum');
                Assert::same(Game::STAT_INFLUENCE, $world->game->pressureLocationCalls[0]['pressureType'], 'influence');
                Assert::count(1, $world->theah->queuedOfType(EventPressureOccuring::class), 'occurring');
                $pressured = $world->theah->queuedOfType(EventLocationPressured::class);
                Assert::same($reaction->Id, $pressured[0]->abilityId, 'ability');
                Assert::true($pressured[0]->success, 'stub');
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'move waits for the result');
            },

            'successful pressure moves one Renown onto the performer location' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure-' . $performer->Id);
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->toLocation, 'to');
                Assert::same(1, $moving[0]->amount, 'one');
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'docks');
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'forum');
                Assert::true($added[0]->isMove, 'marked as a move');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'same batch');
                Assert::same($moving[0]->batchId, $added[0]->batchId, 'add in batch');
                Assert::same($risk->getInjectCode(), $moving[0]->description, 'source');
            },

            'failed pressure does not move Renown' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure-' . $performer->Id);
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'stays');
            },

            'end of turn clears the remembered location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->renown($world));
                Assert::true(str_contains($reaction->getReactionDescription($world->theah), Game::LOCATION_CITY_DOCKS), 'armed');

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                Assert::false(str_contains($reaction->getReactionDescription($world->theah), Game::LOCATION_CITY_DOCKS), 'cleared');
            },

            'ignores a different reaction trigger' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure-' . $performer->Id);
                $world->theah->takeQueuedEvents();

                $event = new EventRiskReactionTriggered();
                $event->internalId = 'other';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->Used, 'unused');
                Assert::count(0, $world->game->pressureLocationCalls, 'no pressure');
            },
        ];
    }
}
