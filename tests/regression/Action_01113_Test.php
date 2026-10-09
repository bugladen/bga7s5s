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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01113;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01113_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01113';
    }

    /**
     * Robbery in hand; Pirate at Docks; opponent attachment in Discard-2.
     *
     * @return array{0:_01113,1:Action_01113,2:GenericCharacter,_01049}
     */
    private function scene(TestWorld $world, bool $pirate = true, bool $discardAttachment = true): array
    {
        $risk = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Pirate', $pirate ? ['Pirate'] : []),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $attachment = null;
        if ($discardAttachment) {
            $attachment = $world->placeCard(new _01049(), $world->game->getPlayerDiscardDeckName(2), 2);
        }
        /** @var Action_01113 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $attachment];
    }

    public function tests(): array
    {
        return [
            'targets cards, not characters' => function () {
                Assert::instanceOf(IAbilityThatTargetsCards::class, new Action_01113(), 'cards');
                Assert::false(
                    new Action_01113() instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters,
                    'not characters'
                );
            },

            'available with a Pirate in city and an attachment in an opponent discard' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Pirate performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Pirate');
            },

            'unavailable when the opponent discard has no attachments' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty discard');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are Pirates in the city only' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $homePirate = $world->placeCharacter(new GenericCharacter('Home Pirate', ['Pirate']), Game::LOCATION_PLAYER_HOME, 1);

                $performers = $action->getPerformersForAction(1, $world->theah);
                $ids = array_map(fn($c) => $c->Id, array_values($performers));

                Assert::true(in_array($pirate->Id, $ids, true), 'city Pirate');
                Assert::false(in_array($homePirate->Id, $ids, true), 'Home excluded by RiskCityAction');
            },

            'trigger queues transition 01113' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01113', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal');
            },

            'step 1 args list opponents who have equippable discard attachments' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01113, 'x');

                Assert::count(1, $args['opponents'], 'one opponent');
                Assert::same(2, $args['opponents'][0]['id'], 'player 2');
            },

            'step 1 records CHOSEN_OPPONENT and goes to playerChosen' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01113, 'x', 2);

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                Assert::same(['playerChosen'], $world->game->gamestate->transitions, 'named');
            },

            'step 1 refuses a non-Pirate performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $plain->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01113, 'x', 2);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'step 2 pulls the attachment into hand and enters pay' => function () {
                $world = new TestWorld();
                [$risk, $action, $pirate, $attachment] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01113_2, 'x', $attachment->Id);

                Assert::same(1, $attachment->ControllerId, 'controller flipped');
                Assert::same($attachment->Id, $world->game->globals->get(Game::CHOSEN_ATTACHMENT), 'chosen');
                Assert::same($attachment->WealthCost, $world->game->globals->get(Game::CHOSEN_CARD_COST), 'cost');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'to hand');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $pay[0]->payStateType, 'equip pay');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('01113_2', $transitions[0]->transition, 'pay step');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'named');
            },

            'pass from step 1 resolves the action' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01113);

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'step 3 pay equips and resolves' => function () {
                $world = new TestWorld();
                [$risk, $action, $pirate, $attachment] = $this->scene($world);
                // Move to hand as step 2 would have done.
                $attachment->Location = Game::LOCATION_HAND;
                $attachment->ControllerId = 1;
                $payCard = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);
                $world->game->globals->set(Game::CHOSEN_CARD_COST, 2);
                $world->game->globals->set(Game::DISCOUNT, 0);

                // WealthCost 2 needs two non-Wealth payment cards; use two Risks as 1 wealth each.
                $pay2 = $world->placeCard(new _01113(), Game::LOCATION_HAND, 1);
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01113_3,
                    'x',
                    [$payCard->Id, $pay2->Id]
                );

                Assert::count(2, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'paid');
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'equipped');
                Assert::same($attachment->Id, $equip[0]->attachmentId, 'attachment');
                Assert::same($pirate->Id, $equip[0]->characterId, 'to performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },
        ];
    }
}
