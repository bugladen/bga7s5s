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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd09;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04cd09;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04cd09_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04cd09';
    }

    /**
     * @return array{0:_04cd09,1:Action_04cd09,2:GenericCharacter}
     */
    private function ready(TestWorld $world): array
    {
        $card = $world->placeCard(new _04cd09(), Game::LOCATION_CITY_DOCKS, 0);
        $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_04cd09 $action */
        $action = $card->getActions()[0];
        return [$card, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is an unlimited city action that does not require a performer up front' => function () {
                $action = new Action_04cd09();
                Assert::instanceOf(EventCityAction::class, $action, 'EventCityAction');
                Assert::false($action->RequiresPerformerSelected, 'performer later');
                Assert::same(1, Action_04cd09::COST_ENGAGE, 'engage id');
                Assert::same(2, Action_04cd09::COST_DISCARD, 'discard id');
            },

            // WHY: Need a character of yours here, then either an unengaged one or a card in hand.
            // Destinations are every other city location, not just adjacent ones.
            'available with an unengaged performer or a card in hand' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->ready($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engage path');

                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged and empty hand');
                $inHand = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028(), Game::LOCATION_HAND, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'discard path');

                $performer->Location = Game::LOCATION_CITY_FORUM;
                $inHand->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody here');

                $performer->Location = Game::LOCATION_CITY_DOCKS;
                $performer->Engaged = false;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
                $action->Used = false;
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent has no character here');
                Assert::same(Game::LOCATION_CITY_DOCKS, $card->Location, 'card stayed');
            },

            'trigger opens the cost choice' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->ready($world);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04cd09', $transition->transition, 'state');
                Assert::same($card->Id, $transition->sourceId, 'card');
            },

            'cost args report which payments are open' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->ready($world);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028(), Game::LOCATION_HAND, 1);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09, '');
                Assert::same($card->Id, $args['cardId'], 'card');
                Assert::true($args['canEngage'], 'engage');
                Assert::true($args['canDiscard'], 'discard');

                $performer->Engaged = true;
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09, '');
                Assert::false($args['canEngage'], 'no unengaged performer');
            },

            'an illegal cost is rejected' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->ready($world);
                $performer->Engaged = true;
                foreach ([Action_04cd09::COST_ENGAGE, Action_04cd09::COST_DISCARD, 9] as $cost) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09, '', $cost);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'cost ' . $cost);
                }
                Assert::same(null, $world->game->globals->get(Action_04cd09::COST_MODE), 'unset');
            },

            'choosing engage or discard stores the mode and clears a stale performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->ready($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, 44);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09, '', Action_04cd09::COST_ENGAGE);
                Assert::same(Action_04cd09::COST_ENGAGE, $world->game->globals->get(Action_04cd09::COST_MODE), 'mode');
                Assert::same(0, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'cleared');
                Assert::same('costChosen', $world->game->gamestate->transitions[0], 'transition');
            },

            'engage cost rejects a stranger, then engages a legal performer and routes through events' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->ready($world);
                $world->game->globals->set(Action_04cd09::COST_MODE, Action_04cd09::COST_ENGAGE);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_2, '', $foe->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'foe');

                $performer->Engaged = true;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_2, '', $performer->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'already engaged');
                $performer->Engaged = false;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_2, '', $performer->Id);
                Assert::same($performer->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'stored');
                $engaged = $world->theah->queuedOfType(EventCardEngaged::class)[0];
                Assert::same($performer->Id, $engaged->cardId, 'performer');
                Assert::same($card->Id, $engaged->sourceId, 'card');
                Assert::same('04cd09_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'next');
                Assert::same('costPaid', $world->game->gamestate->transitions[0], 'through events');
            },

            'discard cost requires a card in the active player hand' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->ready($world);
                $world->game->globals->set(Action_04cd09::COST_MODE, Action_04cd09::COST_DISCARD);
                $theirs = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028(), Game::LOCATION_HAND, 2);
                $mine = $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_2, '', $theirs->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'opponent hand');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_2, '', $mine->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::same($mine->Id, $discard->cardId, 'discarded');
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not a payment flag');
                Assert::false($discard->AsPlayed, 'not played');
                Assert::same($card->Id, $discard->sourceId, 'source');
                Assert::same('04cd09_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'next');
            },

            'location args are every other city location' => function () {
                $world = new TestWorld();
                [$card, $action, $performer] = $this->ready($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_3, '');
                Assert::same($card->Id, $args['cardId'], 'card');
                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'not here');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'not only adjacent');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'home excluded');
            },

            'an illegal destination is rejected' => function () {
                $world = new TestWorld();
                [, $action] = $this->ready($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_3, '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'same location');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },

            // WHY: Unlimited. Central confirm sets Used; resolving clears it.
            'moving the card resolves the action and clears Used' => function () {
                $world = new TestWorld();
                [$card, $action] = $this->ready($world);
                $action->Used = true;
                $world->game->globals->set(Action_04cd09::COST_MODE, Action_04cd09::COST_DISCARD);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, 8);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD09_3, '', [Game::LOCATION_CITY_BAZAAR]);

                $moving = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($card->Id, $moving->cardId, 'the event card');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moving->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moving->toLocation, 'to');
                Assert::false($moving->engage, 'cost already paid');
                Assert::same(1, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'active player');
                Assert::false($action->Used, 'unlimited');
                Assert::count(1, $world->theah->queuedOfType(EventActionUsed::class), 'used event');
                Assert::same(0, $world->game->globals->get(Action_04cd09::COST_MODE), 'mode cleared');
                Assert::same(0, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer cleared');
                Assert::same('locationChosen', $world->game->gamestate->transitions[0], 'transition');
            },
        ];
    }
}
