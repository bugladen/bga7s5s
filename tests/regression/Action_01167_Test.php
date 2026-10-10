<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01167;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01167;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01167_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01167';
    }

    /**
     * Liberating Goods in hand; performer in city; non-Unique attachment in Discard-2.
     *
     * @return array{0:_01167,1:Action_01167,2:GenericCharacter,3:_01049|_01155|null}
     */
    private function scene(TestWorld $world, bool $discardAttachment = true, bool $zeroCost = false): array
    {
        $risk = $world->placeCard(new _01167(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Thief'), Game::LOCATION_CITY_DOCKS, 1);
        $attachment = null;
        if ($discardAttachment) {
            $attachment = $zeroCost
                ? $world->placeCard(new _01155(), $world->game->getPlayerDiscardDeckName(2), 2)
                : $world->placeCard(new _01049(), $world->game->getPlayerDiscardDeckName(2), 2);
        }
        /** @var Action_01167 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $attachment];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets cards' => function () {
                $action = new Action_01167();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'available with a city performer and a non-Unique discard attachment' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when opponent discard has only Unique attachments' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $world->placeCard(new _01047(), $world->game->getPlayerDiscardDeckName(2), 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Unique only');
            },

            'unavailable without a city performer' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world, false);
                $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home only');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are controller\'s city characters that can equip a discard attachment' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'city only');
                Assert::false(in_array($home->Id, $ids, true), 'Home excluded');
            },

            'trigger queues transition 01167' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01167', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal');
            },

            'step 1 args list opponents with equippable non-Unique discard attachments' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01167, 'x');

                Assert::count(1, $args['opponents'], 'one opponent');
                Assert::same(2, $args['opponents'][0]['id'], 'player 2');
            },

            'step 1 records CHOSEN_OPPONENT and goes to opponentChosen' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01167, 'x', 2);

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                Assert::same(['opponentChosen'], $world->game->gamestate->transitions, 'named');
            },

            'step 1 refuses self as opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01167, 'x', 1);
                } catch (UserException | \Exception $e) {
                    $threw = true;
                }
                Assert::true($threw, 'self refused');
            },

            'step 2 args list non-Unique discard attachments for the chosen opponent' => function () {
                $world = new TestWorld();
                [, $action, , $attachment] = $this->scene($world);
                $unique = $world->placeCard(new _01047(), $world->game->getPlayerDiscardDeckName(2), 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01167_2, 'x');

                $ids = array_column($args['cards'], 'id');
                Assert::true(in_array($attachment->Id, $ids, true), 'non-Unique listed');
                Assert::false(in_array($unique->Id, $ids, true), 'Unique filtered');
                Assert::same('Player Two', $args['opponentName'], 'name');
            },

            'step 2 records CHOSEN_CARD and goes to attachmentChosen' => function () {
                $world = new TestWorld();
                [, $action, , $attachment] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01167_2,
                    'x',
                    $attachment->Id
                );

                Assert::same($attachment->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'named');
            },

            'step 2 refuses a Unique attachment' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $unique = $world->placeCard(new _01047(), $world->game->getPlayerDiscardDeckName(2), 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01167_2,
                        'x',
                        $unique->Id
                    );
                } catch (UserException | \Exception $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Unique refused');
            },

            'step 3 chooses performer, queues pay + 01167_3 transition' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $attachment] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, $attachment->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01167_3,
                    'x',
                    $performer->Id
                );

                Assert::same($performer->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $pay[0]->payStateType, 'equip');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01167_3', $transitions[0]->transition, 'pay UI');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(['performerChosen'], $world->game->gamestate->transitions, 'named');
            },

            // WHY: equip from discard pays costs then removes + equips; ActionResolved fires here
            // (not on trigger). Empty payment ids are valid for WealthCost 0.
            'step 4 pay equips from discard, discards payment cards, and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $attachment] = $this->scene($world, true, true);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::DISCOUNT, 0);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01167_4,
                    'x',
                    []
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed');
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'equip');
                Assert::same($attachment->Id, $equip[0]->attachmentId, 'attachment');
                Assert::same($performer->Id, $equip[0]->characterId, 'to performer');
                Assert::same($risk->Id, $equip[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no pay cards');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['attachmentEquipped'], $world->game->gamestate->transitions, 'named');
            },

            'step 4 pay with WealthCost 2 discards payment cards as payment' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $attachment] = $this->scene($world);
                $pay1 = $world->placeCard(new _01167(), Game::LOCATION_HAND, 1);
                $pay2 = $world->placeCard(new _01167(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::DISCOUNT, 0);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01167_4,
                    'x',
                    [$pay1->Id, $pay2->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'paid');
                Assert::true($discards[0]->AsPayment, 'as payment');
                Assert::false($discards[0]->asEffect, 'not effect');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
