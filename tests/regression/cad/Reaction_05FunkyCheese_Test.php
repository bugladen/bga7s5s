<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05FunkyCheese;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05FunkyCheese;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05FunkyCheese_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05FunkyCheese';
    }

    private function pressure(TestWorld $world, string $location, int $playerId = 1): EventPressureOccuring
    {
        $event = new EventPressureOccuring();
        $event->theah = $world->theah;
        $event->location = $location;
        $event->playerId = $playerId;
        $event->performerId = 0;
        $event->pressureTypes = [Game::STAT_INFLUENCE];
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: Covert pressure Reaction — uncontrolled city, pressure here, opposing
            // non-Leader present. Wound Owner then target. Leaders / claimed / elsewhere skip.
            'offers on pressure at uncontrolled location with opposing non-Leader' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_DOCKS));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("wound-{$foe->Id}", $ids, true), 'foe button');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'does not offer when location is claimed' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'not Covert');
            },

            'does not offer when pressure is elsewhere' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            'does not offer when only opposing character is a Leader' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'Leader-only');
            },

            // WHY: pick queues ONLY CharacterTargeted so Cloud Bottle / UL / Maryam can cancel
            // before either wound is emitted.
            'pick queues only CharacterTargeted and spends the reaction' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_DOCKS));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$foe->Id}");

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'foe');
                Assert::same($leichenberg->Id, $targeted[0]->sourceId, 'source');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wounds yet (self-wound gated too)');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: reaction is already Used when the hook arrives — survive branch precedes isAvailable.
            'surviving CharacterTargeted wounds Owner then target sharing the batch' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];
                $reaction->Used = true;

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $reaction->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $leichenberg->Id;
                $targeted->batchId = 77;
                $targeted->theah = $world->theah;
                $reaction->handleEvent($targeted);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'both wounded');
                Assert::same($leichenberg->Id, $wounds[0]->characterId, 'self first');
                Assert::same($foe->Id, $wounds[1]->characterId, 'foe');
                Assert::same($leichenberg->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::same(77, $wounds[0]->batchId, 'self shares batch');
                Assert::same(77, $wounds[1]->batchId, 'target shares batch');
            },

            'canceled or foreign CharacterTargeted queues nothing (no self-wound either)' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $canceled = new EventCharacterTargeted();
                $canceled->abilityId = $reaction->Id;
                $canceled->targetId = $foe->Id;
                $canceled->canceled = true;
                $canceled->theah = $world->theah;
                $reaction->handleEvent($canceled);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');

                $other = new EventCharacterTargeted();
                $other->abilityId = 'other';
                $other->targetId = $foe->Id;
                $other->theah = $world->theah;
                $reaction->handleEvent($other);
                Assert::count(0, $world->theah->queuedEvents, 'other ability');
            },

            'pass does not spend the reaction' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                $reaction->handleEvent($this->pressure($world, Game::LOCATION_CITY_DOCKS));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass keeps it');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'rejects Leaders via isValidTargetForAbility' => function () {
                $world = new TestWorld();
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);
                $boss = $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05FunkyCheese $reaction */
                $reaction = $leichenberg->getReactions()[0];

                [$ok, ] = $reaction->isValidTargetForAbility($world->game, $boss);
                Assert::false($ok, 'Leader rejected');
            },
        ];
    }
}
