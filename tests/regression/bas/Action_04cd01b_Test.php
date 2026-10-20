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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01105;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd01_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd01b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd01b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd01b';
    }

    /**
     * @return array{0:_04cd01,1:Action_04cd01b,2:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $penya = $world->placeCard(new _04cd01(), Game::LOCATION_CITY_DOCKS, 1);
        $penya->AttachedToId = $host->Id;
        /** @var Action_04cd01b $action */
        $action = $penya->getActions()[1];
        return [$penya, $action, $host];
    }

    public function tests(): array
    {
        return [
            // WHY: Wealth on the attachment is not subtracted. handWealthCount is 99.
            // A Risk whose action needs a city character (_01105) is playable only then.
            // Engaged does not hide this action. Used on an equipped copy is cleared.
            'available when an opponent discard holds an affordable playable risk' => function () {
                $world = new TestWorld();
                [$penya, $action, $host] = $this->equipped($world);
                $discard = $world->game->getPlayerDiscardDeckName(2);
                $risk = $world->placeCard(new _01105(), $discard, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'playable');

                $risk->WealthCost = 200;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot afford');
                $risk->WealthCost = 1;

                $risk->Location = $world->game->getPlayerDiscardDeckName(1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'own discard');
                $risk->Location = $discard;

                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $host->Location = Game::LOCATION_CITY_DOCKS;

                $penya->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged attachment still offered');
                $penya->Engaged = false;

                $action->Used = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'stale Used cleared');
                Assert::false($action->Used, 'flag cleared');
                Assert::true(in_array($penya->Id, $world->game->updatedCardObjectIds, true), 'attachment written');

                $penya->AttachedToId = 0;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used and unequipped stays used');
                Assert::true($action->Used, 'flag kept');
            },

            'equipping a used copy clears the flag before the action list builds' => function () {
                $world = new TestWorld();
                [$penya, $action, $host] = $this->equipped($world);
                $action->Used = true;

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $penya->Id;
                $equipped->characterId = $host->Id;
                $equipped->theah = $world->theah;
                $action->handleEvent($equipped);

                Assert::false($action->Used, 'cleared');
                Assert::true($penya->IsUpdated, 'marked updated');

                $action->Used = true;
                $equipped->attachmentId = 999;
                $penya->IsUpdated = false;
                $action->handleEvent($equipped);
                Assert::true($action->Used, 'other attachment');
            },

            'trigger opens opponent choice for the attachment controller' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->equipped($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 2;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04cd01b', $transition->transition, 'state');
                Assert::same(1, $transition->playerId, 'controller');
                Assert::same($penya->Id, $transition->sourceId, 'attachment');
            },

            'opponent args skip the active player, then the discard lists affordable actions' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->equipped($world);
                $world->game->activePlayerId = 1;
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01B, '');
                Assert::same([2], array_column($args['opponents'], 'id'), 'only the opponent');

                $discard = $world->game->getPlayerDiscardDeckName(2);
                $playable = $world->placeCard(new _01028(), $discard, 2);
                $pricey = $world->placeCard(new _01105(), $discard, 2);
                $pricey->WealthCost = 200;
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2, '');
                Assert::same('Player Two', $args['opponentName'], 'name');
                $cardIds = array_column($args['cards'], 'id');
                Assert::true(in_array($playable->Id, $cardIds, true), 'cheap risk listed');
                Assert::true(in_array($pricey->Id, $cardIds, true), 'unaffordable risk still listed');
                $actionIds = array_column($args['actions'], 'id');
                Assert::same([$playable->getActions()[0]->Id], $actionIds, 'only the affordable action');
                Assert::same($penya->ControllerId, 1, 'wealth is the attachment controller');
            },

            'an unknown opponent is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01B, '', 9);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'unset');
            },

            'choosing an opponent stores them and advances' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD01B, '', 2);
                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                Assert::same('opponentChosen', $world->game->gamestate->transitions[0], 'transition');
            },

            // WHY: Sink is paid at commit, to the bottom of the City Deck, and Used is
            // cleared first so a later muster is not stuck. The clone pays; this action
            // does not resolve here. A performer action opens the performer chooser.
            'committing an equipped copy sinks Penya and opens performer selection' => function () {
                $world = new TestWorld();
                [$penya, $action, $host] = $this->equipped($world);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_FORUM, 1);
                $risk = $world->placeCard(new _01105(), $world->game->getPlayerDiscardDeckName(2), 2);
                $action->Used = true;

                $action->actFromActionWithActionId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2,
                    '',
                    $risk->Id,
                    $risk->getActions()[0]->Id
                );

                Assert::false($action->Used, 'cleared before sink');
                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0];
                Assert::same($host->Id, $unequip->characterId, 'host');
                Assert::same($penya->Id, $unequip->attachmentId, 'penya');
                Assert::same($penya->Location, $world->theah->queuedOfType(EventCardRemovedFromPlay::class)[0]->toLocation, 'from play');
                $sink = $world->theah->queuedOfType(EventCardAddedToCityDeck::class)[0];
                Assert::same($penya->Id, $sink->cardId, 'sunk card');
                Assert::false($sink->onTop, 'bottom');

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $risk->Location, 'original hidden');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->cardId, 'left discard');

                $clone = $world->game->createdCardsInLocation[0];
                Assert::instanceOf(_04cd01_RiskClone::class, $clone, 'clone');
                Assert::same($risk->Name, $clone->Name, 'name');
                Assert::same($risk->Image, $clone->Image, 'image');
                Assert::same($risk->getWealthCost(), $clone->getWealthCost(), 'cost');
                Assert::same($risk->Id, $clone->ClonedCardId, 'original id');
                Assert::same(Game::LOCATION_HAND, $clone->Location, 'in hand');
                Assert::same($clone->Id, $world->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'added');

                $clonedAction = $clone->getActions()[0];
                Assert::same($clone->Id . '_Action_01105', $clonedAction->Id, 'owner-scoped id');
                Assert::same($clonedAction->Id, $world->game->globals->get(Game::CHOSEN_ACTION), 'chosen');
                Assert::same($clonedAction->Id, $world->game->globals->get(Game::TRANSITION_INTERNAL_ID), 'internal');
                Assert::true($world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal flow');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('inHandActionChoosePerformer', $transitions[0]->transition, 'performer path');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay yet');
                Assert::same('actionChosen', $world->game->gamestate->transitions[0], 'advance');
            },

            // WHY: Unequipped commit does not sink. An action with no performer goes to pay,
            // and the pay event's card is the clone.
            'an unequipped commit skips the sink and pays for the clone' => function () {
                $world = new TestWorld();
                [$penya, $action] = $this->equipped($world);
                $penya->AttachedToId = 0;
                $risk = $world->placeCard(new _01028(), $world->game->getPlayerDiscardDeckName(2), 2);

                $action->actFromActionWithActionId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04CD01B_2,
                    '',
                    $risk->Id,
                    $risk->getActions()[0]->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDeck::class), 'not sunk');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'not unequipped');
                $clone = $world->game->createdCardsInLocation[0];
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class)[0];
                Assert::same($clone->Id, $pay->cardId, 'clone pays');
                Assert::same(Game::PAY_STATE_IN_HAND_ACTION, $pay->payStateType, 'in-hand action');
                Assert::same('inHandActionPay', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pay');
            },
        ];
    }
}
