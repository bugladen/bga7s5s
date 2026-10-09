<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01102_Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01102;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01102_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01102';
    }

    /**
     * Unfortunate attachment on foe (controller 2); two cards in foe's hand.
     *
     * @return array{0:_01102_Attachment,1:Action_01102,2:GenericCharacter,3:array{0:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card,1:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card}}
     */
    private function scene(TestWorld $world, int $handCount = 2): array
    {
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $att = $world->placeCard(new _01102_Attachment(), Game::LOCATION_CITY_DOCKS, 2);
        $att->AttachedToId = $foe->Id;
        $foe->Attachments[] = $att->Id;
        $original = $world->placeCard(new _01102(), Game::LOCATION_DUELING_LINE, 1);
        $att->OriginalCardId = $original->Id;

        $hand = [];
        for ($i = 0; $i < $handCount; $i++) {
            $hand[] = $world->placeCard(new _01102(), Game::LOCATION_HAND, 2);
        }

        /** @var Action_01102 $action */
        $action = $att->getActions()[0];
        return [$att, $action, $foe, $hand];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_01102(), 'AttachmentAction');
            },

            'available when the equipped character\'s controller has 2+ cards in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 2);
                Assert::true($action->isAvailableToPlayer(2, $world->theah), '2 cards');
            },

            'unavailable with fewer than 2 cards in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), '1 card');
            },

            'unavailable when not attached' => function () {
                $world = new TestWorld();
                $att = $world->placeCard(new _01102_Attachment(), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCard(new _01102(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _01102(), Game::LOCATION_HAND, 2);
                /** @var Action_01102 $action */
                $action = $att->getActions()[0];
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'unattached');
            },

            'unavailable to the non-controller' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not controller');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'used');
            },

            'trigger queues transition 01102' => function () {
                $world = new TestWorld();
                [$att, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01102', $transitions[0]->transition, 'name');
                Assert::same($att->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal');
            },

            // WHY: Discard two from hand, then removeRiskAttachment (unequip/discard/hide + original move).
            'act discards two hand cards, destroys the attachment, and resolves' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01102,
                    'highDramaPlayerTurn_01102',
                    [$hand[0]->Id, $hand[1]->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'two discards');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                // WHY: createAttachmentDiscardedFromPlayEvent routes non-City attachments to
                // EventCardDiscardedFromPlay (not a dedicated AttachmentDiscarded event).
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard attachment');
                Assert::count(1, $world->theah->queuedOfType(EventCardHidden::class), 'hide');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardsChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'act refuses fewer than two ids' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01102,
                        'x',
                        [$hand[0]->Id]
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'need two');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses a card not in hand' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);
                $hand[1]->Location = Game::LOCATION_CITY_FORUM;
                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01102,
                        'x',
                        [$hand[0]->Id, $hand[1]->Id]
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
