<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02021;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02021_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02021';
    }

    /**
     * Anghos in city; optional attachment in hand (walked via FakeGame::dbCards).
     *
     * @return array{0:_02021,1:Reaction_02021,2:?_01048}
     */
    private function scene(TestWorld $world, bool $withAttachment = true, string $anghosLocation = Game::LOCATION_CITY_DOCKS): array
    {
        $anghos = $world->placeCharacter(new _02021(), $anghosLocation, 1);
        $attachment = null;
        if ($withAttachment) {
            /** @var _01048 $attachment */
            $attachment = $world->placeCard(new _01048(), Game::LOCATION_HAND, 1);
        }
        /** @var Reaction_02021 $reaction */
        $reaction = $anghos->getReactions()[0];
        return [$anghos, $reaction, $attachment];
    }

    private function attachmentIdProp(Reaction_02021 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02021::class, 'attachmentId');
        $prop->setAccessible(true);
        return $prop;
    }

    private function enteringPay(TestWorld $world, int $playerId = 1): EventEnteringPayState
    {
        $event = new EventEnteringPayState();
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02021(), 'CardReaction');
            },

            // WHY: offers on EventEnteringPayState when owner in city + hand has attachments
            // (getAttachmentsInHand walks FakeGame dbCards).
            'offers on EventEnteringPayState when Anghos in city with attachment in hand' => function () {
                $world = new TestWorld();
                [$anghos, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->enteringPay($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($anghos->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'does not offer when Anghos is at Home' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, true, Game::LOCATION_PLAYER_HOME);

                $reaction->handleEvent($this->enteringPay($world));
                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            'does not offer without attachments in hand' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, false);

                $reaction->handleEvent($this->enteringPay($world));
                Assert::count(0, $world->theah->queuedEvents, 'no attachment');
            },

            'does not offer for another player pay state' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->enteringPay($world, 2));
                Assert::count(0, $world->theah->queuedEvents, 'other player');
            },

            // WHY: convertToWealth-{id} adds Wealth trait and stores attachmentId.
            'performReaction convertToWealth adds Wealth and stores attachmentId' => function () {
                $world = new TestWorld();
                [, $reaction, $attachment] = $this->scene($world);

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    "convertToWealth-{$attachment->Id}"
                );

                Assert::true($attachment->hasTrait('Wealth'), 'Wealth');
                Assert::same($attachment->Id, $this->attachmentIdProp($reaction)->getValue($reaction), 'stored');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: Decline/pass still setUsed.
            'performReaction decline still setUsed' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::true($reaction->Used, 'used');
                Assert::same(0, $this->attachmentIdProp($reaction)->getValue($reaction), 'no store');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'performReaction pass still setUsed' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::true($reaction->Used, 'used');
                Assert::same(0, $this->attachmentIdProp($reaction)->getValue($reaction), 'no store');
            },

            // WHY: AsPayment discard of stored id removes Wealth, queues CardSentToLocker, clears id.
            'EventCardDiscardedFromHand AsPayment removes Wealth and queues locker' => function () {
                $world = new TestWorld();
                [, $reaction, $attachment] = $this->scene($world);
                $attachment->addTrait($world->game, 'Wealth', true);
                $this->attachmentIdProp($reaction)->setValue($reaction, $attachment->Id);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $attachment->Id;
                $event->ownerId = 1;
                $event->AsPayment = true;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($attachment->hasTrait('Wealth'), 'Wealth removed');
                $lockers = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $lockers, 'locker');
                Assert::same($attachment->Id, $lockers[0]->cardId, 'card');
                Assert::same(0, $this->attachmentIdProp($reaction)->getValue($reaction), 'cleared');
            },

            'EventCardDiscardedFromHand without AsPayment does nothing' => function () {
                $world = new TestWorld();
                [, $reaction, $attachment] = $this->scene($world);
                $attachment->addTrait($world->game, 'Wealth', true);
                $this->attachmentIdProp($reaction)->setValue($reaction, $attachment->Id);

                $event = new EventCardDiscardedFromHand();
                $event->cardId = $attachment->Id;
                $event->ownerId = 1;
                $event->AsPayment = false;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($attachment->hasTrait('Wealth'), 'kept');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no locker');
            },

            // WHY: turn end clears Wealth if the attachment is still in hand.
            'EventPlayerTurnEnd clears Wealth when attachment still in hand' => function () {
                $world = new TestWorld();
                [, $reaction, $attachment] = $this->scene($world);
                $attachment->addTrait($world->game, 'Wealth', true);
                $this->attachmentIdProp($reaction)->setValue($reaction, $attachment->Id);

                $event = new EventPlayerTurnEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($attachment->hasTrait('Wealth'), 'cleared');
                Assert::same(0, $this->attachmentIdProp($reaction)->getValue($reaction), 'id cleared');
            },
        ];
    }
}
