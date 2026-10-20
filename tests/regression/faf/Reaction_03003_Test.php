<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Thomas;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
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

            'decline spends the reaction and musters nothing' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $buratino = $world->placeCharacter(new _01019(), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new _01020(), $world->game->getPlayerDiscardDeckName(1), 1);
                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];
                $event = new EventCharacterDestroyed();
                $event->characterId = $buratino->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterMustered::class), 'no muster');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: Buratino's printed cost is 1, so the -1 discount is free and skips pay.
            // Discard origin queues the leave-discard event before the Home muster.
            'a free discard Thug is mustered at Home without a payment' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_CITY_FORUM, 1);
                $buratino = $world->placeCharacter(new _01019(), $world->game->getPlayerDiscardDeckName(1), 1);
                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];
                $event = new EventCharacterDestroyed();
                $event->characterId = $dante->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("pickDiscard-{$buratino->Id}", $buttons, true), 'discard button');

                $reaction->performReaction($world->game, 0, $reaction->Id, "pickDiscard-{$buratino->Id}");
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                $muster = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::same($buratino->Id, $removed[0]->cardId, 'left discard');
                Assert::same($buratino->Id, $muster[0]->characterId, 'mustered');
                Assert::same(Game::LOCATION_PLAYER_HOME, $muster[0]->location, 'home');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'nothing paid');
            },

            // WHY: Dante costs 2, discounted to 1. One non-Wealth hand card completes payment.
            // The chosen Thug is not itself a pay button when it came from hand.
            'paying the discounted cost discards the payment then musters from hand' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _03003(), Game::LOCATION_CITY_DOCKS, 1);
                $destroyed = $world->placeCharacter(new GenericCharacter('Other', ['Thug']), Game::LOCATION_CITY_FORUM, 1);
                $dante = $world->placeCharacter(new _01020(), Game::LOCATION_HAND, 1);
                $pay = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061(), Game::LOCATION_HAND, 1);
                /** @var Reaction_03003 $reaction */
                $reaction = $don->getReactions()[0];
                $event = new EventCharacterDestroyed();
                $event->characterId = $destroyed->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $reaction->performReaction($world->game, 0, $reaction->Id, "pickHand-{$dante->Id}");
                Assert::false($reaction->Used, 'still paying');
                Assert::same(['done'], $world->game->gamestate->transitions, 'requeue ends the state');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("pay-{$pay->Id}", $buttons, true), 'pay card');
                Assert::false(in_array("pay-{$dante->Id}", $buttons, true), 'chosen Thug is not payment');
                Assert::true(in_array('back', $buttons, true), 'back');

                $world->game->gamestate->transitions = [];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'back');
                Assert::false($reaction->Used, 'back does not spend');
                $pick = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("pickHand-{$dante->Id}", $pick, true), 'returned to the picker');

                $reaction->performReaction($world->game, 0, $reaction->Id, "pickHand-{$dante->Id}");
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, "pay-{$pay->Id}");
                $discarded = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                $muster = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::same($pay->Id, $discarded[0]->cardId, 'payment discarded');
                Assert::true($discarded[0]->AsPayment, 'as payment');
                Assert::same($dante->Id, $muster[0]->characterId, 'dante mustered');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'hand origin');
                Assert::true($reaction->Used, 'spent');
            },
        ];
    }
}
