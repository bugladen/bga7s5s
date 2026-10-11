<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01184;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01184;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01184_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01184';
    }

    /**
     * Controlled Claude in the city at the pressured location.
     *
     * @return array{0:_01184,1:Reaction_01184}
     */
    private function scene(TestWorld $world): array
    {
        $claude = $world->placeCharacter(new _01184(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01184 $reaction */
        $reaction = $claude->getReactions()[0];
        return [$claude, $reaction];
    }

    private function pressure(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): EventPressureOccuring
    {
        $event = new EventPressureOccuring();
        $event->location = $location;
        $event->playerId = 2;
        $event->performerId = 0;
        $event->pressureTypes = [Game::STAT_INFLUENCE];
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01184(), 'CardReaction');
            },

            // WHY (journal 2026-03-31-11): EventPressureOccuring when Claude is controlled,
            // in city, and at the pressured location — sets CLAUDE_PRESSURE_TYPE later on accept.
            'offers when Claude\'s city location is pressured' => function () {
                $world = new TestWorld();
                [$claude, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($claude->Id, $transitions[0]->sourceId, 'Claude');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer when pressure is at another location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            'does not offer when Claude is not in the city' => function () {
                $world = new TestWorld();
                [$claude, $reaction] = $this->scene($world);
                $claude->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_PLAYER_HOME));

                Assert::count(0, $world->theah->queuedEvents, 'Home');
            },

            'does not offer when Claude is uncontrolled' => function () {
                $world = new TestWorld();
                [$claude, $reaction] = $this->scene($world);
                $claude->ControllerId = 0;

                $reaction->handleEvent($this->pressure($world));

                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->pressure($world));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer specialCount and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['specialCount', 'pass'], $ids, 'buttons');
            },

            // WHY (journal 2026-05-10-02): CLAUD_ID is "the card whose location defines the
            // filter," not uniquely Claude — Inauguration Day reuses the same flag.
            'specialCount sets CLAUDE_PRESSURE_TYPE, CLAUD_ID, and marks Used' => function () {
                $world = new TestWorld();
                [$claude, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'specialCount');

                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE),
                    'CLAUDE flag'
                );
                Assert::same($claude->Id, $world->game->globals->get(Game::CLAUD_ID), 'CLAUD_ID');
                Assert::true($reaction->Used, 'used');
                Assert::false($reaction->isAvailable(), 'unavailable');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the reaction available without setting pressure flags' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::true($reaction->isAvailable(), 'available');
                Assert::false(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CLAUDE_PRESSURE_TYPE),
                    'no flag'
                );
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
