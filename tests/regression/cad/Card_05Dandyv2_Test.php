<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Dandyv2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandyv2a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandyv2b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02027;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Dandyv2_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Dandyv2';
    }

    private function placeDeckCards(TestWorld $world, int $count): void
    {
        $deck = $world->game->getPlayerFactionDeckName(1);
        for ($i = 0; $i < $count; $i++)
        {
            $world->placeCard(new GenericCharacter('Deck'), $deck, 1);
        }
    }

    public function tests(): array
    {
        return [
            'is a leader with two character actions' => function () {
                $claude = new _05Dandyv2();
                // WHY: Card stats lagged the test — Resolve 8 (377cce19), CrewCap 6 (e074aca4).
                Assert::same(8, $claude->Resolve, 'Resolve');
                Assert::same(4, $claude->Influence, 'Influence');
                Assert::same(6, $claude->CrewCap, 'CrewCap');
                Assert::true($claude->hasTrait('Leader'), 'Leader trait');
                Assert::count(2, $claude->getActions(), 'two actions');
                Assert::instanceOf(Action_05Dandyv2a::class, $claude->getActions()[0], 'renown move');
                Assert::instanceOf(Action_05Dandyv2b::class, $claude->getActions()[1], 'claude move');
                Assert::instanceOf(CharacterAction::class, $claude->getActions()[0], 'CharacterAction');
            },

            'setup draws three cards when the deck has them' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_PLAYER_HOME, 1);
                $this->placeDeckCards($world, 6);

                $setup = new EventTableSetup();
                $world->fireOn($claude, $setup);

                Assert::count(3, $world->theah->queuedOfType(EventCardDrawn::class), 'three draws');
            },

            'setup draws only what the deck holds' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_PLAYER_HOME, 1);
                $this->placeDeckCards($world, 2);

                $setup = new EventTableSetup();
                $world->fireOn($claude, $setup);

                Assert::count(2, $world->theah->queuedOfType(EventCardDrawn::class), 'two draws');
            },

            'renown at his location lowers Influence by that amount' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $added = new EventRenownAddedToLocation();
                $added->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $added);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'one modifier');
                Assert::same(4, $mods[0]->OldInfluence, 'old');
                Assert::same(2, $mods[0]->NewInfluence, 'new');
                Assert::same(2, $claude->AppliedRenownPenalty, 'penalty');
            },

            'leaving for Home removes the penalty' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $added = new EventRenownAddedToLocation();
                $added->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $added);
                $world->theah->takeQueuedEvents();

                $moved = new EventCardMoved();
                $moved->cardId = $claude->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($claude, $moved);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'restore');
                Assert::same(4, $mods[0]->NewInfluence, 'back to printed');
                Assert::same(0, $claude->AppliedRenownPenalty, 'cleared');
            },

            'a penalty past zero reports a zero chip and still restores' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 10);

                $added = new EventRenownAddedToLocation();
                $added->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $added);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::same(0, $mods[0]->NewInfluence, 'clamped chip');
                Assert::same(-6, $claude->UnclampedInfluence, 'remembers the overflow');

                $world->theah->takeQueuedEvents();
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                $removed = new EventRenownRemovedFromLocation();
                $removed->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $removed);

                $restored = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::same(4, $restored[0]->NewInfluence, 'printed Influence returns');
            },

            'mustering him into a city applies that location\'s Renown' => function () {
                $world = new TestWorld();
                $deck = $world->game->getPlayerFactionDeckName(1);
                $claude = $world->placeCharacter(new _05Dandyv2(), $deck, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 3);

                $mustered = new EventCharacterMustered();
                $mustered->playerId = 1;
                $mustered->characterId = $claude->Id;
                $mustered->location = Game::LOCATION_CITY_FORUM;
                $mustered->fromLocation = $deck;
                $world->fireOn($claude, $mustered);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'one modifier');
                Assert::same(4, $mods[0]->OldInfluence, 'old');
                Assert::same(1, $mods[0]->NewInfluence, 'printed minus renown');
                Assert::same(3, $claude->AppliedRenownPenalty, 'penalty');
            },

            'a pressure uses the already-reduced Influence and does not apply the penalty' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $added = new EventRenownAddedToLocation();
                $added->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $added);
                $world->theah->takeQueuedEvents();

                $pressure = new EventPressureOccuring();
                $pressure->location = Game::LOCATION_CITY_DOCKS;
                $pressure->playerId = 1;
                $pressure->performerId = $claude->Id;
                $pressure->pressureTypes = [Game::STAT_INFLUENCE];
                $world->fireOn($claude, $pressure);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'pressure does not rewrite Influence');
                Assert::same(2, $claude->ModifiedInfluence, 'chip already shows the Renown penalty');
                Assert::same(2, $claude->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'pressure total uses the chip');
            },

            // Action needs Renown here — Home never has City Renown.
            'move action is unavailable at Home' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'move action needs Renown here and an adjacent City' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'has renown');
            },

            // WHY: Printed Action moves Renown, not Claude — Shackles/Harpoon no longer gate it.
            'move action remains available while Shackled' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $claude->addCondition(Game::SHACKLES_CONDITION);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'shackled ok');
            },

            // WHY: Printed Action (not City Action) — "any City" destinations, no ally gate.
            'claude move is available from Home and from a City with no ally' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home');

                $claude->Location = Game::LOCATION_CITY_DOCKS;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'alone in city');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'controlled site ok');
            },

            // WHY: Shackles soft-locked tables that paid then failed eventCheck on
            // location pick. Gate availability (and pay-step) so the Action never
            // enters 05Dandyv2b_2 while Claude cannot move.
            'claude move is unavailable while Shackled' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addCondition(Game::SHACKLES_CONDITION);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'shackled');
            },

            'paying for claude move refuses while Shackled' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addCondition(Game::SHACKLES_CONDITION);
                $first = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                $second = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                Assert::throws(
                    fn() => $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B,
                        'highDramaPhase05Dandyv2b',
                        [$first->Id, $second->Id]
                    ),
                    'Shackled',
                    'refused'
                );
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no pay');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no step 2');
            },

            'claude move location choice while Shackled resolves without moving' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addCondition(Game::SHACKLES_CONDITION);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2,
                    'highDramaPhase05Dandyv2b_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'paying for the move queues the discard then the location step' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $payment = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A,
                    'highDramaPhase05Dandyv2a',
                    [$payment->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same(true, $discards[0]->AsPayment, 'payment');
                Assert::same($payment->Id, $discards[0]->cardId, 'paid card');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('05Dandyv2a_2', $transitions[0]->transition, 'location step');
            },

            'location choice moves Renown to the adjacent City' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2,
                    'highDramaPhase05Dandyv2a_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                Assert::count(1, $moving, 'moving');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving[0]->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->toLocation, 'to forum');

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'from docks');

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_FORUM, $added[0]->location, 'to forum');
                Assert::same(true, $added[0]->isMove, 'is move');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'location choice rejects a non-adjacent City' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_05Dandyv2a $action */
                $action = $claude->getActions()[0];

                // WHY: 2-player adjacency — Docks touches Forum only, not Bazaar.
                Assert::throws(
                    fn() => $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2A_2,
                        'highDramaPhase05Dandyv2a_2',
                        [Game::LOCATION_CITY_BAZAAR]
                    ),
                    'adjacent',
                    'non-adjacent'
                );
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no move');
            },

            'paying for claude move queues the discards then the location step' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $first = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                $second = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B,
                    'highDramaPhase05Dandyv2b',
                    [$first->Id, $second->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'paid');
                Assert::same($first->Id, $discards[0]->cardId, 'first coin');
                Assert::same($second->Id, $discards[1]->cardId, 'second coin');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('05Dandyv2b_2', $transitions[0]->transition, 'location step');
            },

            'one Wealth card pays the claude move cost' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $wealth = $world->placeCard(new GenericCharacter('Purse', ['Wealth']), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B,
                    'highDramaPhase05Dandyv2b',
                    [$wealth->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'one card');
                Assert::same($wealth->Id, $discards[0]->cardId, 'wealth card');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'location step');
            },

            'one plain card does not pay the claude move cost' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $payment = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                $rejected = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B,
                        'highDramaPhase05Dandyv2b',
                        [$payment->Id]
                    );
                } catch (\Bga\GameFramework\UserException) {
                    $rejected = true;
                }

                Assert::true($rejected, 'underpay rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'not discarded');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no step 2');
            },

            'claude move location choice moves without engaging' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2,
                    'highDramaPhase05Dandyv2b_2',
                    [Game::LOCATION_CITY_BAZAAR]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'bazaar');
                Assert::same(false, $moves[0]->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'claude move rejects his current City location' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_05Dandyv2b $action */
                $action = $claude->getActions()[1];

                Assert::throws(
                    fn() => $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05DANDYV2B_2,
                        'highDramaPhase05Dandyv2b_2',
                        [Game::LOCATION_CITY_DOCKS]
                    ),
                    'City location',
                    'current rejected'
                );
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },

            'crystal eye at zero renown raises Influence to 5' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addAttachment($world->theah, $eye);

                $equipped = new EventAttachmentEquipped();
                $equipped->characterId = $claude->Id;
                $equipped->attachmentId = $eye->Id;
                $world->fireOn($claude, $equipped);

                Assert::same(5, $claude->ModifiedInfluence, 'chip');
                Assert::same(5, $claude->UnclampedInfluence, 'tracker includes the +1');
                Assert::same(1, $claude->TrackedAttachmentInfluence, 'folded');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'equip already wrote the chip');
            },

            // WHY: Collect clears Applied/Unclamped immediately, but a leftover InfMod
            // from applying the penalty (queued before a Transition interrupt) can still
            // absolute-write the chip back to 3. Re-sync on InfMod must restore 4.
            'stale penalty InfMod cannot stick after renown is cleared' => function () {
                $world = new TestWorld();
                /** @var _05Dandyv2 $claude */
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                // Post-collect state: Renown gone, tracker restored, chip already 4.
                $claude->UnclampedInfluence = 4;
                $claude->ModifiedInfluence = 4;
                $claude->AppliedRenownPenalty = 0;

                // Leftover from when the penalty was first applied (4→3).
                $stale = EventFactory::createCharacterInfluenceModifiedEvent(
                    1,
                    $claude->Id,
                    4,
                    3,
                    $claude->getInjectCode()
                );
                $world->applyHub($stale);
                $world->fire($stale);

                Assert::same(4, $claude->ModifiedInfluence, 'chip restored');
                Assert::same(4, $claude->UnclampedInfluence, 'tracker');
                Assert::same(0, $claude->AppliedRenownPenalty, 'still cleared');
                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'correction logged');
                Assert::same(3, $mods[0]->OldInfluence, 'from stale chip');
                Assert::same(4, $mods[0]->NewInfluence, 'back to printed');
            },

            'collecting the renown while crystal eye is equipped logs the increase' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $added = new EventRenownAddedToLocation();
                $added->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $added);
                $world->theah->takeQueuedEvents();

                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addAttachment($world->theah, $eye);
                $equipped = new EventAttachmentEquipped();
                $equipped->characterId = $claude->Id;
                $equipped->attachmentId = $eye->Id;
                $world->fireOn($claude, $equipped);
                Assert::same(4, $claude->ModifiedInfluence, '4 - 1 renown + 1 crystal eye');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                $removed = new EventRenownRemovedFromLocation();
                $removed->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($claude, $removed);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'log line');
                Assert::same(4, $mods[0]->OldInfluence, 'old');
                Assert::same(5, $mods[0]->NewInfluence, 'printed plus crystal eye');
                Assert::same(5, $claude->ModifiedInfluence, 'chip');
            },

            'a chip that already lost crystal eye to a renown sync is repaired' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addAttachment($world->theah, $eye);
                // The renown sync overwrote the chip from Unclamped and dropped the +1.
                $claude->UnclampedInfluence = 4;
                $claude->ModifiedInfluence = 4;
                $claude->TrackedAttachmentInfluence = 0;
                $claude->AppliedRenownPenalty = 0;

                $claude->repairAttachmentInfluence($world->theah);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'correction');
                Assert::same(4, $mods[0]->OldInfluence, 'old');
                Assert::same(5, $mods[0]->NewInfluence, 'new');
                Assert::same(5, $claude->ModifiedInfluence, 'chip');
                Assert::same(1, $claude->TrackedAttachmentInfluence, 'folded once');
            },

            'unequipping crystal eye removes the +1 from the tracker' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->addAttachment($world->theah, $eye);
                $equipped = new EventAttachmentEquipped();
                $equipped->characterId = $claude->Id;
                $equipped->attachmentId = $eye->Id;
                $world->fireOn($claude, $equipped);
                $world->theah->takeQueuedEvents();

                $claude->removeAttachment($world->theah, $eye);
                $unequipped = new EventAttachmentUnequipped();
                $unequipped->characterId = $claude->Id;
                $unequipped->attachmentId = $eye->Id;
                $world->fireOn($claude, $unequipped);

                Assert::same(4, $claude->ModifiedInfluence, 'back to printed');
                Assert::same(4, $claude->UnclampedInfluence, 'tracker');
                Assert::same(0, $claude->TrackedAttachmentInfluence, 'cleared');
            },

            // WHY: Dusk queues Engarde before CardMoved home (Moving appends Moved).
            // Without writing Unclamped on Éventail's engarde bump, the Renown
            // restore rebuilds the chip without the +1.
            'eventail en garde bonus survives dusk home move past renown' => function () {
                $world = new TestWorld();
                /** @var _05Dandyv2 $claude */
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $fan = $world->placeCard(new _02027(), Game::LOCATION_CITY_DOCKS, 1);
                $fan->AttachedToId = $claude->Id;
                $claude->Attachments[] = $fan->Id;
                $claude->Engaged = true;
                // Engaged at a city with 1 Renown; Éventail bonus already stripped.
                $claude->UnclampedInfluence = 3;
                $claude->ModifiedInfluence = 3;
                $claude->AppliedRenownPenalty = 1;

                $engarde = new EventCardEngarded();
                $engarde->cardId = $claude->Id;
                $engarde->playerId = 1;
                $world->fire($engarde);

                Assert::same(4, $claude->ModifiedInfluence, 'eventail +1 on chip');
                Assert::same(4, $claude->UnclampedInfluence, 'eventail +1 on tracker');
                Assert::same(1, $claude->SkipExternalUnclampedInfluenceEvents, 'armed skip');

                // InfMod is stacked — drain it before the home move like runEvents would.
                foreach ($world->theah->takeQueuedEvents() as $ev) {
                    if ($ev instanceof EventCharacterInfluenceModified) {
                        $world->applyHub($ev);
                        $world->fire($ev);
                    }
                }
                Assert::same(0, $claude->SkipExternalUnclampedInfluenceEvents, 'skip consumed');
                Assert::same(4, $claude->UnclampedInfluence, 'no double-count');

                $moved = new EventCardMoved();
                $moved->cardId = $claude->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_PLAYER_HOME;
                $moved->engage = false;
                $world->fire($moved);

                Assert::same(5, $claude->ModifiedInfluence, 'printed + eventail at home');
                Assert::same(5, $claude->UnclampedInfluence, 'tracker');
                Assert::same(0, $claude->AppliedRenownPenalty, 'renown cleared');
            },

            // Same dusk race, but home move runs while Éventail's InfMod is still queued
            // (absorb path must keep Unclamped correct without waiting for InfMod).
            'eventail en garde bonus survives home move before InfMod drains' => function () {
                $world = new TestWorld();
                /** @var _05Dandyv2 $claude */
                $claude = $world->placeCharacter(new _05Dandyv2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $fan = $world->placeCard(new _02027(), Game::LOCATION_CITY_DOCKS, 1);
                $fan->AttachedToId = $claude->Id;
                $claude->Attachments[] = $fan->Id;
                $claude->Engaged = true;
                $claude->UnclampedInfluence = 3;
                $claude->ModifiedInfluence = 3;
                $claude->AppliedRenownPenalty = 1;

                $engarde = new EventCardEngarded();
                $engarde->cardId = $claude->Id;
                $engarde->playerId = 1;
                $world->fire($engarde);

                $moved = new EventCardMoved();
                $moved->cardId = $claude->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_PLAYER_HOME;
                $moved->engage = false;
                $world->fire($moved);

                Assert::same(5, $claude->ModifiedInfluence, 'chip after renown clear');
                Assert::same(5, $claude->UnclampedInfluence, 'tracker kept eventail');

                foreach ($world->theah->takeQueuedEvents() as $ev) {
                    if ($ev instanceof EventCharacterInfluenceModified) {
                        $world->applyHub($ev);
                        $world->fire($ev);
                    }
                }

                Assert::same(5, $claude->ModifiedInfluence, 'chip after InfMods');
                Assert::same(5, $claude->UnclampedInfluence, 'no double-count');
                Assert::same(0, $claude->SkipExternalUnclampedInfluenceEvents, 'skips drained');
            },
        ];
    }
}
