<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02048;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02048_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02048';
    }

    /**
     * @return array{0:_02048,1:Reaction_02048,2:GenericCharacter,3:_02049}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02048(), Game::LOCATION_HAND, 1);
        $friend = $world->placeCharacter(new GenericCharacter('Friend'), Game::LOCATION_CITY_DOCKS, 1);
        $enemyRisk = $world->placeCard(new _02049(), Game::LOCATION_HAND, 2);
        /** @var Reaction_02048 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $friend, $enemyRisk];
    }

    private function target(TestWorld $world, int $friendId, int $sourceId): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->targetId = $friendId;
        $event->sourceId = $sourceId;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02048(), 'RiskReaction');
            },

            // Opponent risk that targets characters. The effect is held until pressure resolves.
            'an opponent risk targeting your character offers Combat pressure' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $queued = new EventCardEngaged();
                $queued->batchId = 7;
                $queued->sourceId = $enemyRisk->Id;
                $world->theah->queueEvent($queued);

                $event = $this->target($world, $friend->Id, $enemyRisk->Id);
                $event->batchId = 7;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'held');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'batch deleted');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'owner');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['pressure', 'decline'], $ids, 'buttons');
                Assert::true(str_contains($reaction->getReactionDescription($world->theah), Game::LOCATION_CITY_DOCKS), 'location');
            },

            'engage, move, wound, challenge, and equip from that risk each offer' => function () {
                $cases = [
                    'engage' => function (TestWorld $world, GenericCharacter $friend, int $sourceId): EventCardEngaged {
                        $event = new EventCardEngaged();
                        $event->cardId = $friend->Id;
                        $event->sourceId = $sourceId;
                        return $event;
                    },
                    'move' => function (TestWorld $world, GenericCharacter $friend, int $sourceId): EventCardMoved {
                        $event = new EventCardMoved();
                        $event->cardId = $friend->Id;
                        $event->sourceId = $sourceId;
                        return $event;
                    },
                    'wound' => function (TestWorld $world, GenericCharacter $friend, int $sourceId): EventCharacterBeingWounded {
                        $event = new EventCharacterBeingWounded();
                        $event->characterId = $friend->Id;
                        $event->sourceId = $sourceId;
                        return $event;
                    },
                    'challenge' => function (TestWorld $world, GenericCharacter $friend, int $sourceId): EventChallengeIssued {
                        $event = new EventChallengeIssued();
                        $event->defenderId = $friend->Id;
                        $event->sourceId = $sourceId;
                        return $event;
                    },
                    'equip' => function (TestWorld $world, GenericCharacter $friend, int $sourceId): EventAttachmentEquipping {
                        $event = new EventAttachmentEquipping();
                        $event->characterId = $friend->Id;
                        $event->attachmentId = 50;
                        $event->sourceId = $sourceId;
                        return $event;
                    },
                ];

                foreach ($cases as $name => $make) {
                    $world = new TestWorld();
                    [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                    $event = $make($world, $friend, $enemyRisk->Id);
                    $event->theah = $world->theah;
                    $reaction->handleEvent($event);
                    Assert::true($event->canceled, $name);
                    Assert::count(1, $world->theah->queuedOfType(EventTransition::class), $name . ' offer');
                }
            },

            'does not offer for your own risk, a risk that does not target characters, a target outside the city, or when the card is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $own = $world->placeCard(new _02049(), Game::LOCATION_HAND, 1);
                $plain = $world->placeCard(new _02041(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->target($world, $friend->Id, $own->Id));
                Assert::count(0, $world->theah->queuedEvents, 'own risk');

                $reaction->handleEvent($this->target($world, $friend->Id, $plain->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not a targeting risk');

                $friend->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in city');

                $friend->Location = Game::LOCATION_CITY_DOCKS;
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                Assert::count(0, $world->theah->queuedEvents, 'risk not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'pressure enters the in-hand reaction pay state' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure');

                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'type');
                Assert::same($reaction->Id, $pay[0]->internalId, 'reaction');
                Assert::false($reaction->Used, 'not used until the reaction is paid');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: Decline puts the held effect back and skips the next copy so it does not offer again.
            'decline re-emits the held effect and skips the next one' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $held = $this->target($world, $friend->Id, $enemyRisk->Id);
                $reaction->handleEvent($held);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                $reemitted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $reemitted, 'back');
                Assert::false($reemitted[0]->canceled, 'live');
                Assert::same($friend->Id, $reemitted[0]->targetId, 'target');
                Assert::false($reaction->Used, 'unused');

                $world->theah->takeQueuedEvents();
                $reemitted[0]->theah = $world->theah;
                $reaction->handleEvent($reemitted[0]);
                Assert::false($reemitted[0]->canceled, 'skipped');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no re-offer');

                $again = $this->target($world, $friend->Id, $enemyRisk->Id);
                $reaction->handleEvent($again);
                Assert::true($again->canceled, 'offers again after the skip');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'second offer');
            },

            'paying the reaction pressures the character location with Combat' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $world->theah->takeQueuedEvents();

                $event = new EventRiskReactionTriggered();
                $event->internalId = $reaction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->Used, 'used');
                Assert::same(0, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'no performer');
                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'player');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->pressureLocationCalls[0]['location'], 'location');
                Assert::same(Game::STAT_COMBAT, $world->game->pressureLocationCalls[0]['pressureType'], 'combat');
                Assert::same(null, $world->game->pressureLocationCalls[0]['performerId'], 'location pressure');
                Assert::count(1, $world->theah->queuedOfType(EventPressureOccuring::class), 'occurring');
                $pressured = $world->theah->queuedOfType(EventLocationPressured::class);
                Assert::count(1, $pressured, 'pressured');
                Assert::same($reaction->Id, $pressured[0]->abilityId, 'ability');
                Assert::true($pressured[0]->success, 'stub success');
            },

            'successful pressure keeps the held effect canceled' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterTargeted::class), 'not re-emitted');
            },

            'a later effect from the same risk is canceled after pressure succeeds' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);
                $world->theah->takeQueuedEvents();

                $later = new EventCardEngaged();
                $later->cardId = $friend->Id;
                $later->sourceId = $enemyRisk->Id;
                $later->theah = $world->theah;
                $reaction->handleEvent($later);

                Assert::true($later->canceled, 'auto-cancel');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no new offer');
            },

            'failed pressure puts the held effect back' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $back = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $back, 're-emitted');
                Assert::false($back[0]->canceled, 'live');
                Assert::same($friend->Id, $back[0]->targetId, 'target');
            },

            // WHY: End of turn drops the latch. A later effect is no longer auto-canceled, and Used still blocks a new offer.
            'end of turn stops auto-cancel and drops a held effect that never resolved' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                $later = new EventCardEngaged();
                $later->cardId = $friend->Id;
                $later->sourceId = $enemyRisk->Id;
                $later->theah = $world->theah;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($later);
                Assert::false($later->canceled, 'latch cleared');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'still used');
            },

            'end of turn before the pressure result forgets the held effect' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $reaction->handleEvent($this->target($world, $friend->Id, $enemyRisk->Id));
                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterTargeted::class), 'forgotten');
            },

            'successful pressure on a held equip discards that attachment' => function () {
                $world = new TestWorld();
                [, $reaction, $friend, $enemyRisk] = $this->scene($world);
                $attachment = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 2);

                $equipping = new EventAttachmentEquipping();
                $equipping->characterId = $friend->Id;
                $equipping->attachmentId = $attachment->Id;
                $equipping->playerId = 2;
                $equipping->sourceId = $enemyRisk->Id;
                $equipping->theah = $world->theah;
                $reaction->handleEvent($equipping);
                $world->theah->takeQueuedEvents();

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlay::class);
                $discarded = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($attachment->Id, $removed[0]->cardId, 'card');
                Assert::count(1, $discarded, 'discarded');
                Assert::same($attachment->Id, $discarded[0]->cardId, 'same');
            },
        ];
    }
}
