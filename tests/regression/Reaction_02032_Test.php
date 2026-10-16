<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02032_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02032';
    }

    /**
     * @return array{0:_02032,1:Reaction_02032,2:Risk}
     */
    private function scene(TestWorld $world): array
    {
        $lucas = $world->placeCharacter(new _02032(), Game::LOCATION_CITY_DOCKS, 1);
        $risk = $world->placeCard(new _02038(), 'Locker-1', 1);
        /** @var Reaction_02032 $reaction */
        $reaction = $lucas->getReactions()[0];
        return [$lucas, $reaction, $risk];
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02032(), 'CardReaction');
            },

            // Offers only when Damned himself musters and a Risk is in the locker.
            'muster with a locker Risk queues the reaction' => function () {
                $world = new TestWorld();
                [$lucas, $reaction, $risk] = $this->scene($world);

                $event = new EventCharacterMustered();
                $event->characterId = $lucas->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($lucas->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'player');

                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_column($buttons, 'reaction');
                Assert::true(in_array('putRisk-' . $risk->Id, $ids, true), 'risk button');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'does not offer when the locker has no Risk' => function () {
                $world = new TestWorld();
                $lucas = $world->placeCharacter(new _02032(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_02032 $reaction */
                $reaction = $lucas->getReactions()[0];

                $event = new EventCharacterMustered();
                $event->characterId = $lucas->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'does not offer for a different character mustering' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $event = new EventCharacterMustered();
                $event->characterId = 999;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [$lucas, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $event = new EventCharacterMustered();
                $event->characterId = $lucas->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'lists every locker Risk plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction, $risk] = $this->scene($world);
                $second = $world->placeCard(new _02038(), 'Locker-1', 1);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('putRisk-' . $risk->Id, $ids, true), 'first');
                Assert::true(in_array('putRisk-' . $second->Id, $ids, true), 'second');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            // putRisk pulls the card out of the locker into hand and marks Used.
            'putRisk queues locker removal and hand add' => function () {
                $world = new TestWorld();
                [, $reaction, $risk] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'putRisk-' . $risk->Id);

                $removed = $world->theah->queuedOfType(EventCardRemovedFromLocker::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($risk->Id, $removed[0]->cardId, 'card');
                Assert::same(1, $removed[0]->playerId, 'player');

                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $added, 'hand');
                Assert::same($risk->Id, $added[0]->cardId, 'same card');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not move a card or mark Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromLocker::class), 'no remove');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'no hand');
                Assert::false($reaction->Used, 'unused');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
