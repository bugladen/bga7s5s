<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01174;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01174;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01174_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01174';
    }

    /**
     * Risk in hand; non-Unique attachment equipped on a host.
     *
     * @return array{0:_01174,1:Action_01174,2:Character,3:Attachment}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01174(), Game::LOCATION_HAND, 1);
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
        $att = $this->equip($world, new _01049(), $host);
        /** @var Action_01174 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $host, $att];
    }

    private function equip(TestWorld $world, Attachment $attachment, Character $host): Attachment
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
        return $placed;
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets cards' => function () {
                $action = new Action_01174();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'available when a non-Unique attachment is in play' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when only Unique attachments are in play' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01174(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
                $this->equip($world, new _01047(), $host);
                /** @var Action_01174 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Unique only');
            },

            'unavailable with no attachments in play' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01174(), Game::LOCATION_HAND, 1);
                /** @var Action_01174 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'none');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'trigger queues transition 01174' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01174', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY (journal 2026-09-23-04): label includes controller + host so duplicate
            // attachment copies are unambiguous in the button list.
            'args label attachments with controller and host name' => function () {
                $world = new TestWorld();
                [, $action, $host, $att] = $this->scene($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01174,
                    'highDramaPlayerTurn_01174'
                );

                Assert::count(1, $args['attachments'], 'one');
                Assert::same($att->Id, $args['attachments'][0]['id'], 'id');
                Assert::contains($att->Name, $args['attachments'][0]['name'], 'attachment name');
                Assert::contains('Player Two', $args['attachments'][0]['name'], 'controller');
                Assert::contains($host->Name, $args['attachments'][0]['name'], 'host');
            },

            'args omit Unique attachments' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $this->equip($world, new _01047(), $host);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01174,
                    'highDramaPlayerTurn_01174'
                );

                Assert::count(1, $args['attachments'], 'non-Unique only');
            },

            // WHY (journal 2026-10-08-01): act queues ONLY CharacterTargeted with attachment Id
            // so Unyielding Loyalty can hold the cancel hook before unequip/discard.
            'act queues only CharacterTargeted (attachment Id) with batchId' => function () {
                $world = new TestWorld();
                [$risk, $action, , $att] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01174,
                    'highDramaPlayerTurn_01174',
                    $att->Id
                );

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one');
                Assert::same($att->Id, $targeted[0]->targetId, 'attachment Id');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'no unequip yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'no discard yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            // WHY: surviving CharacterTargeted (Pass / NoD) emits unequip+discard+resolve
            // with shared batchId so UL can still sweep the effect package.
            'surviving CharacterTargeted queues unequip, discard-as-effect, ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $host, $att] = $this->scene($world);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $att->Id;
                $targeted->sourceId = $risk->Id;
                $targeted->batchId = 77;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($att->Id, $unequip[0]->attachmentId, 'attachment');
                Assert::same($host->Id, $unequip[0]->characterId, 'host');
                Assert::same(77, $unequip[0]->batchId, 'shared batch');

                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discard, 'discard');
                Assert::same($att->Id, $discard[0]->cardId, 'card');
                Assert::same($risk->Id, $discard[0]->sourceId, 'source');
                Assert::true($discard[0]->asEffect, 'as effect');
                Assert::same(77, $discard[0]->batchId, 'batched');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventAttachmentUnequipped::class, $order, true)
                        < array_search(EventCardDiscardedFromPlay::class, $order, true),
                    'unequip before discard'
                );
            },

            'canceled CharacterTargeted queues no destroy effects' => function () {
                $world = new TestWorld();
                [, $action, , $att] = $this->scene($world);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $att->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'UL cancel holds effects');
            },

            'act refuses an invalid attachment id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01174,
                        'x',
                        99999
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses an attachment still in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $handAtt = $world->placeCard(new _01049(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01174,
                        'x',
                        $handAtt->Id
                    );
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'in hand');
            },
        ];
    }
}
