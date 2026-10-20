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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03060_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03060';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03060();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Sorcerer is the only performer gate. Wounds are not a cost, so an unwounded
            // location still plays and resolves the sorcery with no chooser. Optional engage
            // discounts the printed wealth only for this action. Pass and engage both set abnormal flow.
            'a sorcerer heals two wounds from someone else, or resolves with nobody to heal' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03060(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_03060 $action */
                $action = $risk->getActions()[0];
                Assert::same([$sorcerer->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'sorcerer');
                unset($plain);

                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $sorcerer, $action, $explanations), 'no engage');
                $risk->WillEngage = true;
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $sorcerer, $action, $explanations), 'ignore cost');

                $pay = new EventEnteringPayState();
                $pay->theah = $world->theah;
                $pay->cardId = $risk->Id;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                $action->handleEvent($pay);
                Assert::false($risk->WillEngage, 'cleared');
                Assert::same('03060_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'engage choice');

                $sorcerer->Engaged = true;
                $world->theah->queuedEvents = [];
                $risk->WillEngage = true;
                $action->handleEvent($pay);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'already engaged');
                $sorcerer->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_ACTION, $action->Id);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03060_2, 'x', 2);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bad choice');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03060_2, 'x', 1);
                Assert::true($risk->WillEngage, 'will engage');
                Assert::same($sorcerer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::true((bool) $world->game->globals->get(Game::ABNORMAL_FLOW), 'abnormal');

                $world->game->globals->set(Game::ABNORMAL_FLOW, false);
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_03060_2);
                Assert::true((bool) $world->game->globals->get(Game::ABNORMAL_FLOW), 'pass abnormal');

                $wounded = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_DOCKS, 2);
                $wounded->Wounds = 3;
                $world->theah->queuedEvents = [];
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03060', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'heal chooser');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03060, 'x', $sorcerer->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not the performer');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03060, 'x', $wounded->Id);
                Assert::same(2, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'two');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'nobody wounded still starts and resolves the sorcery' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03060(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no chooser');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
            },
        ];
    }
}
