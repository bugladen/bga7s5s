<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01204;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04029_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04029';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde Merchant in the city. The attachment is unequipped through
            // play-discard, then hand, then equip pay. A Villain leader skips the draw
            // the last controller would otherwise get. Home is not "this location".
            'steals an attachment and draws unless your leader is a villain' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04029(), Game::LOCATION_HAND, 1);
                $merchant = $world->placeCharacter(new _04023(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $bow->WealthCost = 1;
                $bow->AttachedToId = $foe->Id;
                $foe->Attachments = [$bow->Id];
                $pay = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
                /** @var Action_04029 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $merchant->Id);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'steal available');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029, '', $bow->Id);
                Assert::same($foe->ControllerId, (int) $world->game->globals->get(Game::CHOSEN_OPPONENT), 'last controller');
                Assert::same($merchant->ControllerId, $bow->ControllerId, 'control taken before the move');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequip');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0]->cardId, 'through play discard');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'into hand');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'equip pay');
                Assert::same('04029_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pay step');

                $bow->Location = Game::LOCATION_HAND;
                $world->game->globals->set(Game::DISCOUNT, 0);
                $world->game->globals->set(Game::DISCOUNT_EXPLAINATIONS, '');
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029_2, '', [$pay->Id]);
                Assert::same($merchant->Id, $world->theah->queuedOfType(EventAttachmentEquipping::class)[0]->characterId, 'equipped to merchant');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'last controller draws');

                $leader = new GenericLeader('Villain');
                $leader->addTrait($world->game, 'Villain');
                $world->theah->leadersByPlayerId[1] = $leader;
                $bow->Location = Game::LOCATION_HAND;
                $bow->ControllerId = 1;
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029_2, '', [$pay->Id]);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'villain leader skips the draw');

                $merchant->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');

                $threw = false;
                try {
                    $world->game->globals->set(Game::CHOSEN_CARD_COST, 5);
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029_2, '', [$pay->Id]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'short payment');
            },

            // WHY: "cannot be moved" is the steal lock. Syrneth Hand must not be a target.
            'refuses an attachment that cannot be moved' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04029(), Game::LOCATION_HAND, 1);
                $merchant = $world->placeCharacter(new _04023(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $hand = $world->placeCard(new _01204(), Game::LOCATION_CITY_DOCKS, 2);
                // WHY: zero the equip cost so a wealth shortfall cannot hide a missing canBeMoved gate.
                $hand->WealthCost = 0;
                $hand->AttachedToId = $foe->Id;
                $foe->Attachments = [$hand->Id];
                /** @var Action_04029 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $merchant->Id);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no legal steal');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029, '');
                Assert::same([], $args['attachments'], 'Syrneth Hand hidden');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04029, '', $hand->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'act refused');
                Assert::same(2, $hand->ControllerId, 'control unchanged');
            },
        ];
    }
}
