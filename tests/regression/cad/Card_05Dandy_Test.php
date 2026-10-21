<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Dandy;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Dandyb;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Dandy_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Dandy';
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
                $claude = new _05Dandy();
                Assert::same(7, $claude->Resolve, 'Resolve');
                Assert::same(4, $claude->Influence, 'Influence');
                Assert::same(7, $claude->CrewCap, 'CrewCap');
                Assert::true($claude->hasTrait('Leader'), 'Leader trait');
                Assert::count(2, $claude->getActions(), 'two actions');
                Assert::instanceOf(Action_05Dandya::class, $claude->getActions()[0], 'move action');
                Assert::instanceOf(Action_05Dandyb::class, $claude->getActions()[1], 'collect action');
                Assert::instanceOf(CharacterAction::class, $claude->getActions()[0], 'CharacterAction');
            },

            'setup draws four cards when the deck has them' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                $this->placeDeckCards($world, 6);

                $setup = new EventTableSetup();
                $world->fireOn($claude, $setup);

                Assert::count(4, $world->theah->queuedOfType(EventCardDrawn::class), 'four draws');
            },

            'setup draws only what the deck holds' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                $this->placeDeckCards($world, 2);

                $setup = new EventTableSetup();
                $world->fireOn($claude, $setup);

                Assert::count(2, $world->theah->queuedOfType(EventCardDrawn::class), 'two draws');
            },

            'renown at his location lowers Influence by that amount' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), $deck, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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

            'move action is available at Home when another city exists' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Dandya $action */
                $action = $claude->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home move');
            },

            'collect is available only in a city that has Renown' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Dandyb $action */
                $action = $claude->getActions()[1];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');

                $claude->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'has renown');
            },

            'paying for the move queues the discard then the location step' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                $payment = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandya $action */
                $action = $claude->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYA,
                    'highDramaPhase05Dandya',
                    [$payment->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same(true, $discards[0]->AsPayment, 'payment');
                Assert::same($payment->Id, $discards[0]->cardId, 'paid card');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('05Dandya_2', $transitions[0]->transition, 'location step');
            },

            'location choice moves Claude without engaging' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Dandya $action */
                $action = $claude->getActions()[0];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYA_2,
                    'highDramaPhase05Dandya_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');
                Assert::same(false, $moves[0]->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'collect pays 2 Wealth then removes one Renown and gains it' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);
                $first = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                $second = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyb $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYB,
                    'highDramaPhase05Dandyb',
                    [$first->Id, $second->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'paid');
                Assert::same($first->Id, $discards[0]->cardId, 'first coin');
                Assert::same($second->Id, $discards[1]->cardId, 'second coin');
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'docks');
                Assert::same(1, $removed[0]->amount, 'one');
                $gained = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gained, 'gained');
                Assert::same(1, $gained[0]->amount, 'one renown');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'one Wealth card pays the collect cost' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $wealth = $world->placeCard(new GenericCharacter('Purse', ['Wealth']), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyb $action */
                $action = $claude->getActions()[1];

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DANDYB,
                    'highDramaPhase05Dandyb',
                    [$wealth->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'one card');
                Assert::same($wealth->Id, $discards[0]->cardId, 'wealth card');
                Assert::count(1, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'collected');
            },

            'one plain card does not pay the collect cost' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $payment = $world->placeCard(new GenericCharacter('Coin'), Game::LOCATION_HAND, 1);
                /** @var Action_05Dandyb $action */
                $action = $claude->getActions()[1];

                $rejected = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_05DANDYB,
                        'highDramaPhase05Dandyb',
                        [$payment->Id]
                    );
                } catch (\Bga\GameFramework\UserException) {
                    $rejected = true;
                }

                Assert::true($rejected, 'underpay rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'not discarded');
                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'not collected');
            },

            'crystal eye at zero renown raises Influence to 5' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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

            'collecting the renown while crystal eye is equipped logs the increase' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
                $claude = $world->placeCharacter(new _05Dandy(), Game::LOCATION_CITY_DOCKS, 1);
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
        ];
    }
}
