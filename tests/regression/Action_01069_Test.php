<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01075;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01069_Test extends TestCase
{
    private const DISCARD = 'Discard-1';

    public function name(): string
    {
        return 'Action_01069';
    }

    /** @return array{0:_01069,1:object,2:object} maxime, handCard, discardedHat */
    private function arm(TestWorld $world): array
    {
        $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
        $handCard = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 1);
        $hat = $world->placeCard(new _01073(), self::DISCARD, 1);
        return [$maxime, $handCard, $hat];
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer ability (Start/Played events required by hook)' => function () {
                Assert::instanceOf(ISorcererAbility::class, new Action_01069(), 'ISorcererAbility');
            },

            'available with Sorcerer, hand card and non-Unique attachment in discard' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with empty hand' => function () {
                $world = new TestWorld();
                [$maxime, $handCard] = $this->arm($world);
                $handCard->Location = Game::LOCATION_CITY_FORUM;
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no hand');
            },

            'unavailable without Sorcerer trait' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                $maxime->ModifiedTraits = array_values(array_filter(
                    $maxime->ModifiedTraits,
                    fn($t) => $t !== 'Sorcerer'
                ));
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'lost Sorcerer');
            },

            // WHY: Text says "non-Unique attachment" — Tabard (Unique) alone must not enable the Action.
            'unavailable when only a Unique attachment is in discard' => function () {
                $world = new TestWorld();
                [$maxime, , $hat] = $this->arm($world);
                $hat->Location = Game::LOCATION_CITY_FORUM;
                $world->placeCard(new _01075(), self::DISCARD, 1);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unique only');
            },

            'unavailable when discard pile has no attachments' => function () {
                $world = new TestWorld();
                [$maxime, , $hat] = $this->arm($world);
                $hat->Location = Game::LOCATION_CITY_FORUM;
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty discard');
            },

            'unavailable when Fate\'s Silence blanks Maxime' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                $maxime->addCondition(Game::FATES_SILENCE_CONDITION);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01069' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01069', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'trigger throws when no non-Unique attachment in discard' => function () {
                $world = new TestWorld();
                [$maxime, , $hat] = $this->arm($world);
                $hat->Location = Game::LOCATION_CITY_FORUM;
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $action->handleEvent($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'throws');
            },

            'step 1 parks hand card, records CHOSEN_CARD and transitions cardChosen' => function () {
                $world = new TestWorld();
                [$maxime, $handCard] = $this->arm($world);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069, 'highDramaPlayerTurn_01069', $handCard->Id);

                Assert::same([$handCard->Id], $world->game->parkedCardIds, 'parked');
                Assert::same($handCard->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen card');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'step 1 refuses card not in hand' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069, 'x', $far->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->game->parkedCardIds, 'nothing parked');
            },

            'step 1 refuses opponent-controlled card' => function () {
                $world = new TestWorld();
                [$maxime] = $this->arm($world);
                $theirs = $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_HAND, 2);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069, 'x', $theirs->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not controlled');
            },

            'step 2 discards hand card, starts sorcerer ability and queues 01069_3' => function () {
                $world = new TestWorld();
                [$maxime, $handCard, $hat] = $this->arm($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $handCard->Id);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_2, 'x', $hat->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard from hand');
                Assert::same($handCard->Id, $discards[0]->cardId, 'discarded hand card');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'sorcerer start');
                Assert::same('01069_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
                Assert::same($hat->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen is now the attachment');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            // WHY regression: Unique attachments must be refused at the choose step, not just hidden in args.
            'step 2 refuses Unique attachment' => function () {
                $world = new TestWorld();
                [$maxime, $handCard] = $this->arm($world);
                $tabard = $world->placeCard(new _01075(), self::DISCARD, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $handCard->Id);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_2, 'x', $tabard->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'unique refused');
                Assert::count(0, $world->theah->queuedEvents, 'no events queued');
            },

            'step 2 refuses card outside the discard pile' => function () {
                $world = new TestWorld();
                [$maxime, $handCard] = $this->arm($world);
                $elsewhere = $world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $handCard->Id);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_2, 'x', $elsewhere->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in discard');
            },

            'step 2 refuses non-attachment card in discard' => function () {
                $world = new TestWorld();
                [$maxime, $handCard] = $this->arm($world);
                $char = $world->placeCharacter(new GenericCharacter('Dead'), self::DISCARD, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $handCard->Id);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_2, 'x', $char->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not attachment');
            },

            'step 3 returns attachment to hand and resolves with sorcerer played' => function () {
                $world = new TestWorld();
                [$maxime, , $hat] = $this->arm($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $hat->Id);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $action->stateFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_3, 'x');

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $removed, 'removed from discard');
                Assert::same($hat->Id, $removed[0]->cardId, 'removed hat');
                Assert::count(1, $added, 'added to hand');
                Assert::same($hat->Id, $added[0]->cardId, 'added hat');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'sorcerer played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'args for step 2 list only non-Unique attachments in discard' => function () {
                $world = new TestWorld();
                [$maxime, , $hat] = $this->arm($world);
                $tabard = $world->placeCard(new _01075(), self::DISCARD, 1);
                /** @var Action_01069 $action */
                $action = $maxime->getActions()[0];

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01069_2, 'x');

                Assert::same($maxime->Id, $args['performerId'], 'performer');
                Assert::true(isset($args['cards'][$hat->Id]), 'hat offered');
                Assert::false(isset($args['cards'][$tabard->Id]), 'unique hidden');
            },
        ];
    }
}
