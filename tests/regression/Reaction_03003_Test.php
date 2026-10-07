<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Thomas;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03003_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03003';
    }

    public function tests(): array
    {
        return [
            // WHY regression: Action_01019 destroy + Dante in discard + Bruno in hand
            // failed to offer — discard queried with location_arg=playerId while Brute
            // destroy writes location_arg=0; Bruno is not a legal hand muster outside duel.
            'offers when Buratino destroyed and Dante in discard (Bruno hand ignored)' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCard(new _05Thomas(), Game::LOCATION_HAND, 1);

                $dante = $world->placeCharacter(
                    new _01020(),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );
                // WHY: Simulate production discard rows (location_arg=0 / ControllerId wiped
                // on the DB filter path). TestTheah filters by ControllerId when playerId
                // is passed — zeroing it reproduces the empty-discard bug.
                $dante->ControllerId = 0;

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($don->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer when only Bruno is in hand (cannot muster from hand outside duel)' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCard(new _05Thomas(), Game::LOCATION_HAND, 1);

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'Bruno alone is not eligible');
            },

            'offers Bruno from hand when Thug destroyed during a duel' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCard(new _05Thomas(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'Bruno legal in duel');
            },

            'does not offer when Don is at Home (City Reaction)' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_PLAYER_HOME, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new _01020(),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'Home blocks City Reaction');
            },

            'offers for hand Thug that can enter play from hand' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCard(new _01020(), Game::LOCATION_HAND, 1);

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'hand Dante offered');
            },

            'does not offer for opponent Thug destroyed' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $enemyThug = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(
                    new _01020(),
                    $world->game->getPlayerDiscardDeckName(1),
                    1
                );

                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventCharacterDestroyed();
                $event->characterId = $enemyThug->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'enemy Thug ignored');
            },
        ];
    }
}
