<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01118;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01118_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01118';
    }

    /** @return array{0:_01118,1:Reaction_01118} */
    private function scene(TestWorld $world, string $at = Game::LOCATION_CITY_DOCKS): array
    {
        $elina = $world->placeCharacter(new _01118(), $at, 1);
        /** @var Reaction_01118 $reaction */
        $reaction = $elina->getReactions()[0];
        return [$elina, $reaction];
    }

    private function sorcererPlayed(TestWorld $world, int $sourceId, int $performerId = 0): EventSorcererAbilityPlayed
    {
        $event = new EventSorcererAbilityPlayed();
        $event->playerId = 1;
        $event->sourceId = $sourceId;
        $event->performerId = $performerId;
        $event->abilityId = 'test';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'offers when Elina performs a Sorcerer ability in the city' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->sorcererPlayed($world, $elina->Id, $elina->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($elina->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'offers when Elina is the performer even if source is elsewhere' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->sorcererPlayed($world, 0, $elina->Id));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'performer match');
            },

            'does not offer at Home' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world, Game::LOCATION_PLAYER_HOME);

                $reaction->handleEvent($this->sorcererPlayed($world, $elina->Id, $elina->Id));

                Assert::count(0, $world->theah->queuedEvents, 'Home');
            },

            'does not offer for an unrelated Sorcerer ability' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->sorcererPlayed($world, 999, 888));

                Assert::count(0, $world->theah->queuedEvents, 'unrelated');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->sorcererPlayed($world, $elina->Id, $elina->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: Renown only exists on city locations — Home is not offered as a source.
            'buttons list city locations with Renown (excluding Elina\'s) plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');

                Assert::true(in_array('moveRenown-' . Game::LOCATION_CITY_FORUM, $ids, true), 'Forum');
                Assert::false(in_array('moveRenown-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'own excluded');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'at Home buttons are Pass only' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, Game::LOCATION_PLAYER_HOME);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');

                Assert::same(['pass'], $ids, 'pass only');
            },

            'moveRenown moves one Renown to Elina\'s location and marks Used' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'moveRenown-' . Game::LOCATION_CITY_FORUM
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->fromLocation, 'from Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->toLocation, 'to Docks');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'batch');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'added');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: Elina may leave the city after the offer was queued — skip rather than crash EventHub.
            'moveRenown while at Home finishes without moving Renown' => function () {
                $world = new TestWorld();
                [$elina, $reaction] = $this->scene($world);
                $elina->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'moveRenown-' . Game::LOCATION_CITY_FORUM
                );

                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the Reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'Dusk resets Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::false($reaction->Used, 'reset');
            },
        ];
    }
}
