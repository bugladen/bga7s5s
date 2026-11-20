<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Terraria;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Quack;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Quack_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Quack';
    }

    private function discard(TestWorld $world, int $cardId, int $ownerId = 1): EventCardDiscardedFromHand
    {
        $event = new EventCardDiscardedFromHand();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->ownerId = $ownerId;
        $event->sourceId = 0;
        $event->AsPayment = false;
        $event->AsPlayed = false;
        $event->asEffect = true;
        return $event;
    }

    private function discardFromPlay(TestWorld $world, int $cardId, int $ownerId = 1): EventCardDiscardedFromPlay
    {
        $event = new EventCardDiscardedFromPlay();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->ownerId = $ownerId;
        $event->fromLocation = Game::LOCATION_CITY_DOCKS;
        $event->sourceId = $cardId;
        $event->asEffect = true;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: Covert + you discarded Discovery/Alquimia + opposing target present.
            'offers when you discard Discovery at uncontrolled city with opposing target' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $discovery = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $discovery->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("wound-{$foe->Id}", $ids, true), 'foe button');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'offers when you discard Alquimia' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $alquimia = $world->placeCard(new _04040(), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $alquimia->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'Alquimia');
            },

            // WHY: Terraria destroy cost is FromPlay, not FromHand — hand-only missed Cloud Bottle.
            'offers when Cloud Bottle Alquimia is discarded from play' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bottle = $world->placeCard(new _05Terraria(), Game::LOCATION_CITY_DOCKS, 1);
                $bottle->AttachedToId = $quack->Id;
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discardFromPlay($world, $bottle->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'from-play Alquimia');
            },

            'does not offer when opponent discards Alquimia from play' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bottle = $world->placeCard(new _05Terraria(), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discardFromPlay($world, $bottle->Id, 2));
                Assert::count(0, $world->theah->queuedEvents, 'not you');
            },

            'does not offer when location is claimed' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $discovery = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $discovery->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not Covert');
            },

            'does not offer when opponent discards' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $discovery = $world->placeCard(new _04039(), Game::LOCATION_HAND, 2);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $discovery->Id, 2));
                Assert::count(0, $world->theah->queuedEvents, 'not you');
            },

            'does not offer for a non-Discovery non-Alquimia discard' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $plain = $world->placeCard(new GenericCharacter('Plain'), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $plain->Id));
                Assert::count(0, $world->theah->queuedEvents, 'wrong trait');
            },

            'wounding queues target and spends the reaction' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $discovery = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $discovery->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, "wound-{$foe->Id}");

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wounded');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($quack->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not spend the reaction' => function () {
                $world = new TestWorld();
                $quack = $world->placeCharacter(new _05Quack(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $discovery = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                /** @var Reaction_05Quack $reaction */
                $reaction = $quack->getReactions()[0];

                $reaction->handleEvent($this->discard($world, $discovery->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass keeps it');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },
        ];
    }
}
