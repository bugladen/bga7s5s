<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01070;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01070;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01070_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01070';
    }

    private function renown(TestWorld $world, int $playerId, string $location, bool $isMove = false): EventRenownAddedToLocation
    {
        $event = new EventRenownAddedToLocation();
        $event->playerId = $playerId;
        $event->location = $location;
        $event->amount = 1;
        $event->isMove = $isMove;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'offers when controller adds Renown and has a card in hand' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $reaction->handleEvent($this->renown($world, 1, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction internal id');
                Assert::contains(Game::LOCATION_CITY_FORUM, $reaction->getReactionDescription($world->theah), 'description names location');
            },

            // WHY: Text "(Moving Renown is not adding Renown.)"
            'does not offer for moved Renown' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $reaction->handleEvent($this->renown($world, 1, Game::LOCATION_CITY_FORUM, true));

                Assert::count(0, $world->theah->queuedEvents, 'move ignored');
            },

            'does not offer when opponent adds Renown' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $reaction->handleEvent($this->renown($world, 2, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'opponent Renown ignored');
            },

            'does not offer with empty hand (discard cost unpayable)' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $reaction->handleEvent($this->renown($world, 1, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'no hand');
            },

            // WHY: Without the Used gate the bonus Renown (also EventRenownAddedToLocation) would re-offer forever.
            'does not offer again once used' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];
                $reaction->Used = true;

                $reaction->handleEvent($this->renown($world, 1, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons list each hand card plus Pass' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $a = $world->placeCard(new GenericCharacter('A'), Game::LOCATION_HAND, 1);
                $b = $world->placeCard(new GenericCharacter('B'), Game::LOCATION_HAND, 1);
                $world->placeCard(new GenericCharacter('Theirs'), Game::LOCATION_HAND, 2);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $ids = array_map(fn($p) => $p['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::true(in_array("addAnotherRenown-{$a->Id}", $ids, true), 'A');
                Assert::true(in_array("addAnotherRenown-{$b->Id}", $ids, true), 'B');
                Assert::true(in_array('pass', $ids, true), 'pass');
                Assert::count(3, $ids, 'opponent hand excluded');
            },

            'perform discards chosen card and adds another Renown to the same location' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $hand = $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];
                $reaction->handleEvent($this->renown($world, 1, Game::LOCATION_CITY_BAZAAR));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "addAnotherRenown-{$hand->Id}");

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'discarded hand card');
                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'another renown');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $renown[0]->location, 'same location');
                Assert::same(1, $renown[0]->amount, 'amount');
                Assert::same(1, $renown[0]->playerId, 'controller');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'nextState done');
            },

            'pass does not discard, add Renown or mark used' => function () {
                $world = new TestWorld();
                $urraca = $world->placeCharacter(new _01070(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                /** @var Reaction_01070 $reaction */
                $reaction = $urraca->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no renown');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'nextState done');
            },
        ];
    }
}
