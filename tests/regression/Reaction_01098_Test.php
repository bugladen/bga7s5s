<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01098_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01098';
    }

    /** @return array{0:_01098,1:Reaction_01098} */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var Reaction_01098 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction];
    }

    private function embargo(Card $card): Card
    {
        $card->addCondition(Game::CATS_EMBARGO_TARGET);
        return $card;
    }

    private function offered(TestWorld $world): int
    {
        return count($world->theah->queuedOfType(EventTransition::class));
    }

    private function discardFromHand(TestWorld $world, Card $card): EventCardDiscardedFromHand
    {
        $event = new EventCardDiscardedFromHand();
        $event->ownerId = $card->ControllerId;
        $event->cardId = $card->Id;
        $event->theah = $world->theah;
        return $event;
    }

    private function discardFromPlay(TestWorld $world, Card $card): EventCardDiscardedFromPlay
    {
        $event = new EventCardDiscardedFromPlay();
        $event->ownerId = $card->ControllerId;
        $event->cardId = $card->Id;
        $event->theah = $world->theah;
        return $event;
    }

    private function equipped(TestWorld $world, int $playerId, int $attachmentId): EventAttachmentEquipped
    {
        $event = new EventAttachmentEquipped();
        $event->playerId = $playerId;
        $event->attachmentId = $attachmentId;
        $event->theah = $world->theah;
        return $event;
    }

    private function combatCard(TestWorld $world, int $playerId, int $cardId): EventCombatCardAnnounced
    {
        $event = new EventCombatCardAnnounced();
        $event->playerId = $playerId;
        $event->cardId = $cardId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // ---- discards ----
            'offers when an opponent discards a stamped card from hand' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new GenericCharacter('Embargoed'), Game::LOCATION_HAND, 2));

                $reaction->handleEvent($this->discardFromHand($world, $card));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($scheme->ControllerId, $transitions[0]->playerId, 'scheme controller');
            },

            'offers when an opponent discards a stamped card from play' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 2));

                $reaction->handleEvent($this->discardFromPlay($world, $card));

                Assert::same(1, $this->offered($world), 'offered');
            },

            // WHY: city attachments leave play via EventCardAddedToCityDiscardPile, not FromPlay.
            'offers when an opponent discards a stamped card to the city discard pile' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 2));

                $event = new EventCardAddedToCityDiscardPile();
                $event->cardId = $card->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(1, $this->offered($world), 'city discard offered');
            },

            // WHY: stamps written before the constant rename (OLD_CATS_EMBARGO_TARGET) must still be honored.
            'honors the legacy embargo stamp' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $world->placeCard(new GenericCharacter('Legacy'), Game::LOCATION_HAND, 2);
                $card->addCondition(Game::OLD_CATS_EMBARGO_TARGET);

                $reaction->handleEvent($this->discardFromHand($world, $card));

                Assert::same(1, $this->offered($world), 'legacy stamp');
            },

            'does not offer when the discarded card is not stamped' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $world->placeCard(new GenericCharacter('Plain'), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->discardFromHand($world, $card));

                Assert::same(0, $this->offered($world), 'not embargoed');
            },

            // WHY (journal 2026-04-10): "an opponent" - a stamped card that changed hands to the scheme controller must not pay out.
            'does not offer when the stamped card is controlled by the scheme controller' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new GenericCharacter('Mine Now'), Game::LOCATION_HAND, 1));

                $reaction->handleEvent($this->discardFromHand($world, $card));
                $reaction->handleEvent($this->discardFromPlay($world, $card));

                Assert::same(0, $this->offered($world), 'own card');
            },

            // ---- equips ----
            'offers when an opponent equips a stamped attachment' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $hat = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 2));

                $reaction->handleEvent($this->equipped($world, 2, $hat->Id));

                Assert::same(1, $this->offered($world), 'offered');
            },

            'does not offer when the scheme controller equips the stamped attachment' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $hat = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 2));

                $reaction->handleEvent($this->equipped($world, 1, $hat->Id));

                Assert::same(0, $this->offered($world), 'journal fix: opponent check on equip');
            },

            'does not offer when an unstamped attachment is equipped' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->equipped($world, 2, $hat->Id));

                Assert::same(0, $this->offered($world), 'not embargoed');
            },

            // ---- combat cards ----
            // WHY (journal 2026-04-10): "plays a card" uses EventCombatCardAnnounced, not the stat-calculation event.
            'offers when an opponent announces a stamped combat card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new GenericCharacter('Combat Card'), Game::LOCATION_HAND, 2));

                $reaction->handleEvent($this->combatCard($world, 2, $card->Id));

                Assert::same(1, $this->offered($world), 'offered');
            },

            'does not offer when the scheme controller announces the stamped combat card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new GenericCharacter('Combat Card'), Game::LOCATION_HAND, 2));

                $reaction->handleEvent($this->combatCard($world, 1, $card->Id));

                Assert::same(0, $this->offered($world), 'own announce');
            },

            // ---- mustering / approach ----
            'offers when an opponent musters a stamped character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $character = $this->embargo($world->placeCharacter(new GenericCharacter('Mustered'), Game::LOCATION_CITY_DOCKS, 2));

                $event = new EventCharacterMustered();
                $event->playerId = 2;
                $event->characterId = $character->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(1, $this->offered($world), 'offered');
            },

            'offers when an opponent plays a stamped Approach character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $character = $this->embargo($world->placeCharacter(new GenericCharacter('Approach'), Game::LOCATION_APPROACH, 2));

                $event = new EventApproachCharacterPlayed();
                $event->playerId = 2;
                $event->characterId = $character->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(1, $this->offered($world), 'offered');
            },

            'does not offer when the scheme controller musters the stamped character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $character = $this->embargo($world->placeCharacter(new GenericCharacter('Mustered'), Game::LOCATION_CITY_DOCKS, 2));

                $event = new EventCharacterMustered();
                $event->playerId = 1;
                $event->characterId = $character->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(0, $this->offered($world), 'own muster');
            },

            'offers when an opponent musters a stamped non-character card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DECK, 2));

                $event = new EventCardMustered();
                $event->playerId = 2;
                $event->cardId = $card->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(1, $this->offered($world), 'offered');
            },

            'does not offer when the scheme controller musters the stamped non-character card' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new _01073(), Game::LOCATION_CITY_DECK, 2));

                $event = new EventCardMustered();
                $event->playerId = 1;
                $event->cardId = $card->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::same(0, $this->offered($world), 'own muster');
            },

            // ---- once per turn ----
            'does not offer again once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $card = $this->embargo($world->placeCard(new GenericCharacter('Embargoed'), Game::LOCATION_HAND, 2));
                $reaction->Used = true;

                $reaction->handleEvent($this->discardFromHand($world, $card));
                $reaction->handleEvent($this->equipped($world, 2, $card->Id));
                $reaction->handleEvent($this->combatCard($world, 2, $card->Id));

                Assert::same(0, $this->offered($world), 'used');
            },

            // WHY: "once per turn" is enforced by Used being reset at Dusk (CardReaction::handleEvent).
            'dusk resets the used flag so it can pay out again next turn' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $event = new EventDuskEndOfDay();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->isAvailable(), 'available again');
            },

            // ---- performReaction ----
            'buttons offer only Gain Renown' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $ids = array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::same(['gainReknown'], $ids, 'no pass button');
            },

            'gainReknown gives the scheme controller one Renown, marks Used and finishes' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gainReknown');

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'one Renown event');
                Assert::same($scheme->ControllerId, $gains[0]->playerId, 'scheme controller');
                Assert::same(1, $gains[0]->amount, '1 Renown');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activation announced');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'gainReknown when already used gains nothing but still finishes' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gainReknown');

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no second Renown');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
