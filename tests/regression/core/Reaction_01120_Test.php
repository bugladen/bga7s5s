<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01120;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01120;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01120_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01120';
    }

    /** @return array{0:_01120,1:Reaction_01120} */
    private function scene(TestWorld $world): array
    {
        $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01120 $reaction */
        $reaction = $pavel->getReactions()[0];
        return [$pavel, $reaction];
    }

    private function claimed(TestWorld $world, int $playerId, string $location = Game::LOCATION_CITY_FORUM): EventLocationClaimed
    {
        $event = new EventLocationClaimed();
        $event->playerId = $playerId;
        $event->location = $location;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: "After an opponent claims" — own claim must not offer.
            'offers when an opponent claims a location' => function () {
                $world = new TestWorld();
                [$pavel, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 2));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($pavel->Id, $transitions[0]->sourceId, 'source Pavel');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same(1, $transitions[0]->playerId, 'Pavel controller');
            },

            'does not offer when Pavel\'s controller claims' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 1));

                Assert::count(0, $world->theah->queuedEvents, 'own claim');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->claimed($world, 2));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer Draw Card and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['drawCard', 'pass'], $ids, 'buttons');
            },

            'drawCard queues a draw, marks Used, and finishes' => function () {
                $world = new TestWorld();
                [$pavel, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same($pavel->ControllerId, $draws[0]->playerId, 'Pavel draws');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->claimed($world, 2));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
