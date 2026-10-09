<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01124;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01124_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01124';
    }

    /** @return array{0:_01124,1:Reaction_01124,2:_01076} */
    private function scene(TestWorld $world): array
    {
        $vedma = $world->placeCharacter(new _01124(), Game::LOCATION_CITY_DOCKS, 1);
        $sorcery = $world->placeCard(new _01076(), Game::LOCATION_HAND, 1);
        /** @var Reaction_01124 $reaction */
        $reaction = $vedma->getReactions()[0];
        return [$vedma, $reaction, $sorcery];
    }

    private function engaged(TestWorld $world, int $cardId, int $playerId, int $sourceId): EventCardEngaged
    {
        $event = new EventCardEngaged();
        $event->cardId = $cardId;
        $event->playerId = $playerId;
        $event->sourceId = $sourceId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: "after you play a Sorcery from your hand that engaged Ved'ma"
            'offers when a hand Sorcery engages Ved\'ma' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);

                $reaction->handleEvent($this->engaged($world, $vedma->Id, 1, $sorcery->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($vedma->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer when a non-Sorcery Risk engages Ved\'ma' => function () {
                $world = new TestWorld();
                [$vedma, $reaction] = $this->scene($world);
                $plain = $world->placeCard(new _01033(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->engaged($world, $vedma->Id, 1, $plain->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not Sorcery');
            },

            'does not offer when a different character is engaged' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);
                $ally = $world->placeCharacter(new \Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->engaged($world, $ally->Id, 1, $sorcery->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not Ved\'ma');
            },

            'does not offer when the opponent engages Ved\'ma' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);

                $reaction->handleEvent($this->engaged($world, $vedma->Id, 2, $sorcery->Id));

                Assert::count(0, $world->theah->queuedEvents, 'opponent');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->engaged($world, $vedma->Id, 1, $sorcery->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons offer En Garde and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['enGarde', 'pass'], $ids, 'buttons');
            },

            'enGarde queues EventCardEngarded, marks Used, and finishes' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);
                $reaction->handleEvent($this->engaged($world, $vedma->Id, 1, $sorcery->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'enGarde');

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($vedma->Id, $engardes[0]->cardId, 'Ved\'ma');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the reaction available' => function () {
                $world = new TestWorld();
                [$vedma, $reaction, $sorcery] = $this->scene($world);
                $reaction->handleEvent($this->engaged($world, $vedma->Id, 1, $sorcery->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no en garde');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
