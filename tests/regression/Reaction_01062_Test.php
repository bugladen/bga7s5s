<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01062_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01062';
    }

    private function accepted(TestWorld $world, int $challengerId): EventChallengeAccepted
    {
        $event = new EventChallengeAccepted();
        $event->challengerId = $challengerId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'offers when challenge accepted at Odette location with adjacent Renown' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->handleEvent($this->accepted($world, $challenger->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($odette->Id, $transitions[0]->sourceId, 'source Odette');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer when no adjacent location has Renown' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->handleEvent($this->accepted($world, $challenger->Id));

                Assert::count(0, $world->theah->queuedEvents, 'no Renown');
            },

            'does not offer when challenge is at another location' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->handleEvent($this->accepted($world, $challenger->Id));

                Assert::count(0, $world->theah->queuedEvents, 'other location');
            },

            'does not offer when Odette is at Home' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_PLAYER_HOME, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_PLAYER_HOME, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->handleEvent($this->accepted($world, $challenger->Id));

                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            'does not offer once used this day' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->Used = true;
                $reaction->handleEvent($this->accepted($world, $challenger->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: intervening swaps the challenged target; reaction also covers that path using CHOSEN_PERFORMER.
            'offers after intervene when challenger shares Odette location' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $event = new EventCharacterIntervened();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'buttons list adjacent Renown locations (no Home) plus Pass' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                // Bazaar also adjacent to Forum but empty: must not get a button.

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_map(fn($b) => $b['reaction'], $buttons);

                Assert::true(in_array('moveFrom-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'Docks');
                Assert::false(in_array('moveFrom-' . Game::LOCATION_CITY_BAZAAR, $ids, true), 'empty Bazaar');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'moveFrom queues one batched Renown move and marks Used' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'moveFrom-' . Game::LOCATION_CITY_FORUM
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $moving, 'moving');
                Assert::count(1, $removed, 'removed');
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->fromLocation, 'from Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->toLocation, 'to Odette');
                Assert::same(Game::LOCATION_CITY_FORUM, $removed[0]->location, 'removed at Forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'added at Docks');
                Assert::true($added[0]->isMove, 'flagged as move');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'same batch (remove)');
                Assert::same($moving[0]->batchId, $added[0]->batchId, 'same batch (add)');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done transition');
            },

            'pass does not move Renown or mark Used' => function () {
                $world = new TestWorld();
                $odette = $world->placeCharacter(new _01062(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01062 $reaction */
                $reaction = $odette->getReactions()[0];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done transition');
            },
        ];
    }
}
