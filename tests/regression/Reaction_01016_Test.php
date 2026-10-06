<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01016;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01016_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01016';
    }

    public function tests(): array
    {
        return [
            'offers when you claim with opposing character and own engaged character' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];

                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'scheme source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer without opposing character' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no foe');
            },

            'does not offer without own engaged character' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no engaged own');
            },

            // WHY regression: "you claim" — opponent claim must not offer
            'does not offer when opponent claims' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventLocationClaimed();
                $event->playerId = 2;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'opponent claim');
            },

            'does not offer when reaction already used' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $reaction->Used = true;

                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used blocks');
            },

            'isValidTargetForAbility requires own engaged at claimed location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $far->Engaged = true;
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Engaged = true;

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->isValidTargetForAbility($world->game, $own)[0], 'own engaged ok');
                Assert::false($reaction->isValidTargetForAbility($world->game, $far)[0], 'wrong location');
                Assert::false($reaction->isValidTargetForAbility($world->game, $foe)[0], 'foe rejected');
            },

            'performReaction en gardes target and marks used' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01016(), Game::LOCATION_PLAYER_HOME, 1);
                $own = $world->placeCharacter(new GenericCharacter('Own'), Game::LOCATION_CITY_DOCKS, 1);
                $own->Engaged = true;
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01016 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'enGarde-' . $own->Id);

                // WHY regression: En Garde queues EventCardEngarded (Engaged=false)
                $engardes = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(1, $engardes, 'en garde');
                Assert::same($own->Id, $engardes[0]->cardId, 'target');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
