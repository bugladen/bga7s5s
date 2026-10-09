<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01116;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01116a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01116a';
    }

    /** @return array{0:_01116,1:Reaction_01116a} */
    private function scene(TestWorld $world): array
    {
        $yevgeni = $world->placeCharacter(new _01116(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01116a $reaction */
        $reaction = $yevgeni->getReactions()[0];
        return [$yevgeni, $reaction];
    }

    private function rejected(TestWorld $world, int $challengerId, int $targetId): EventChallengeRejected
    {
        $event = new EventChallengeRejected();
        $event->challengerId = $challengerId;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'buttons offer En Garde and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['enGarde', 'pass'], $ids, 'buttons');
            },

            'offers when Yevgeni\'s challenge is refused' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->rejected($world, $yevgeni->Id, $foe->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($yevgeni->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer when someone else\'s challenge is refused' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->rejected($world, $ally->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not Yevgeni');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->Used = true;

                $reaction->handleEvent($this->rejected($world, $yevgeni->Id, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: createCardEngardedEvent sets Engaged=false via EventHub (en garde after refuse).
            'enGarde queues EventCardEngarded, marks Used, and finishes' => function () {
                $world = new TestWorld();
                [$yevgeni, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'enGarde');

                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($yevgeni->Id, $engardes[0]->cardId, 'Yevgeni');
                Assert::same($yevgeni->Id, $engardes[0]->sourceId, 'source');
                Assert::same($reaction->Id, $engardes[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the Reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no en garde');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'Dusk resets the Reaction' => function () {
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
