<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01099;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01099b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01099b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01099b';
    }

    /** @return array{0:_01099,1:Reaction_01099b} */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var Reaction_01099b $reaction */
        $reaction = $scheme->getReactions()[1];
        return [$scheme, $reaction];
    }

    private function claimed(TestWorld $world, int $playerId, string $location): EventLocationClaimed
    {
        $event = new EventLocationClaimed();
        $event->playerId = $playerId;
        $event->performerId = null;
        $event->location = $location;
        $event->theah = $world->theah;
        return $event;
    }

    /** @return list<string> */
    private function buttonIds(TestWorld $world, Reaction_01099b $reaction): array
    {
        return array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));
    }

    public function tests(): array
    {
        return [
            'offers when an opponent claims a location' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($scheme->ControllerId, $transitions[0]->playerId, 'scheme controller decides');
            },

            'does not offer when the scheme controller claims a location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 1, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'own claim');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY (journal 2026-04-09): "a different location" - the claimed location is excluded from the buttons.
            'buttons list every city location except the claimed one, plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_FORUM));

                $ids = $this->buttonIds($world, $reaction);

                Assert::false(in_array('addReknown-' . Game::LOCATION_CITY_FORUM, $ids, true), 'claimed location excluded');
                foreach ([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR, Game::LOCATION_CITY_OLES_INN, Game::LOCATION_CITY_GOVERNORS_GARDEN] as $location) {
                    Assert::true(in_array('addReknown-' . $location, $ids, true), $location . ' offered');
                }
                Assert::same('pass', end($ids), 'Pass is last');
                Assert::count(5, $ids, 'four locations + pass');
            },

            'the excluded location follows the most recent claim' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_FORUM));
                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                $ids = $this->buttonIds($world, $reaction);

                Assert::true(in_array('addReknown-' . Game::LOCATION_CITY_FORUM, $ids, true), 'Forum offered again');
                Assert::false(in_array('addReknown-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'Docks excluded');
            },

            'choosing a location adds one Renown there for the scheme controller and marks Used' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'addReknown-' . Game::LOCATION_CITY_BAZAAR);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'one Renown event');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $added[0]->location, 'chosen location');
                Assert::same(1, $added[0]->amount, '1 Renown');
                Assert::same($scheme->ControllerId, $added[0]->playerId, 'scheme controller');
                Assert::same($scheme->getInjectCode(), $added[0]->description, 'attributed to the scheme');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activation announced');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'location names containing apostrophes survive the button id round trip' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'addReknown-' . Game::LOCATION_CITY_OLES_INN);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_OLES_INN, $added[0]->location, "Ole's Inn");
            },

            'pass adds no Renown and leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no Renown');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
