<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01117;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01117_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01117';
    }

    /** @return array{0:_01117,1:Reaction_01117} */
    private function scene(TestWorld $world, int $renown = 1): array
    {
        $eka = $world->placeCharacter(new _01117(), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, $renown);
        /** @var Reaction_01117 $reaction */
        $reaction = $eka->getReactions()[0];
        return [$eka, $reaction];
    }

    private function claimed(TestWorld $world, int $playerId, string $location): EventLocationClaimed
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
            'buttons offer Remove Renown and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['removeReknown', 'pass'], $ids, 'buttons');
            },

            'offers when an opponent claims Ekaterina\'s location with Renown' => function () {
                $world = new TestWorld();
                [$eka, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($eka->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'her controller');
            },

            'does not offer when you claim your own location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->claimed($world, 1, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'own claim');
            },

            'does not offer when the claim is elsewhere' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'other location');
            },

            'does not offer when the location has no Renown left' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, 0);

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'no renown');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->claimed($world, 2, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'removeReknown queues RenownRemoved and marks Used' => function () {
                $world = new TestWorld();
                [$eka, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'removeReknown');

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'Docks');
                Assert::same(1, $removed[0]->amount, 'one');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the Reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'no remove');
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
