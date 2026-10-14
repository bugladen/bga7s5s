<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02019;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02019_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02019';
    }

    /**
     * @return array{0:_02019,1:Reaction_02019,2:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = []): array
    {
        $risk = $world->placeCard(new _02019(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Performer', $traits),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Reaction_02019 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $performer];
    }

    private function locationProp(Reaction_02019 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02019::class, 'Location');
        $prop->setAccessible(true);
        return $prop;
    }

    private function performerProp(Reaction_02019 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02019::class, 'PerformerId');
        $prop->setAccessible(true);
        return $prop;
    }

    private function pressure(
        TestWorld $world,
        array $types,
        string $location = Game::LOCATION_CITY_DOCKS
    ): EventPressureOccuring {
        $event = new EventPressureOccuring();
        $event->location = $location;
        $event->playerId = 1;
        $event->performerId = 0;
        $event->pressureTypes = $types;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02019(), 'RiskReaction');
            },

            // WHY (journal 2026-03-23-02): Influence-only — Combat-only pressure must not offer.
            'offers on EventPressureOccuring with Influence when card in hand and character at location' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world, [Game::STAT_INFLUENCE]));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(
                    Game::LOCATION_CITY_DOCKS,
                    $this->locationProp($reaction)->getValue($reaction),
                    'location armed'
                );
            },

            'does not offer for Combat-only pressure' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world, [Game::STAT_COMBAT]));
                Assert::count(0, $world->theah->queuedEvents, 'combat only');
            },

            'does not offer when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->pressure($world, [Game::STAT_INFLUENCE]));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer without a character at the pressured location' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_CITY_FORUM;

                $reaction->handleEvent($this->pressure($world, [Game::STAT_INFLUENCE]));
                Assert::count(0, $world->theah->queuedEvents, 'no character');
            },

            'performReaction add1ToTotalForWounds sets PerformerId and queues pay' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "add1ToTotalForWounds-{$performer->Id}"
                );

                Assert::same($performer->Id, $this->performerProp($reaction)->getValue($reaction), 'performer');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->cardId, 'card');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not set PerformerId or queue pay' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same(null, $this->performerProp($reaction)->getValue($reaction), 'no performer');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },

            // WHY: wound performer, OR TRIAL_OF_FAITH_PRESSURE_TYPE, setUsed, clear Location.
            'EventRiskReactionTriggered wounds performer and sets pressure flag' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $performer] = $this->scene($world);
                $this->performerProp($reaction)->setValue($reaction, $performer->Id);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($performer->Id, $wounds[0]->characterId, 'performer');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::TRIAL_OF_FAITH_PRESSURE_TYPE),
                    'flag'
                );
                Assert::true($reaction->Used, 'used');
                Assert::same('', $this->locationProp($reaction)->getValue($reaction), 'location cleared');
            },

            // WHY: Zealot with Wounds>0 may heal after pressure result; always clear PerformerId.
            'EventLocationPressureResult heals wounded Zealot and clears PerformerId' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world, ['Zealot']);
                $performer->Wounds = 1;
                $this->performerProp($reaction)->setValue($reaction, $performer->Id);

                $result = new EventLocationPressureResult();
                $result->location = Game::LOCATION_CITY_DOCKS;
                $result->success = true;
                $result->playerId = 1;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($performer->Id, $heals[0]->characterId, 'performer');
                Assert::same(null, $this->performerProp($reaction)->getValue($reaction), 'cleared');
            },

            'EventLocationPressureResult does not heal non-Zealot but clears PerformerId' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $performer->Wounds = 1;
                $this->performerProp($reaction)->setValue($reaction, $performer->Id);

                $result = new EventLocationPressureResult();
                $result->location = Game::LOCATION_CITY_DOCKS;
                $result->success = true;
                $result->playerId = 1;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
                Assert::same(null, $this->performerProp($reaction)->getValue($reaction), 'cleared');
            },
        ];
    }
}
