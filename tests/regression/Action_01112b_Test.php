<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01112;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/**
 * WHY (journal 2026-08-06-01): discardable city card idiom —
 * ICityDeckCard + !controlled + canBeDiscardedFromCity + cardInCity.
 */
final class TestCityCard_01112b extends CityAttachment
{
    public bool $Discardable = true;

    public function __construct(bool $discardable = true)
    {
        parent::__construct();
        $this->Name = 'Test City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Discardable = $discardable;
        $this->resetCard();
    }

    public function canBeDiscardedFromCity(): bool
    {
        return $this->Discardable;
    }
}

class Action_01112b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01112b';
    }

    /**
     * Carnaval in hand; optional uncontrolled discardable city card at Docks.
     * P1 is first player unless $first is false.
     *
     * @return array{0:_01112,1:Action_01112b,?TestCityCard_01112b}
     */
    private function scene(TestWorld $world, bool $first = false, bool $withCity = true): array
    {
        $carnaval = $world->placeCard(new _01112(), Game::LOCATION_HAND, 1);
        $world->game->globals->set(Game::FIRST_PLAYER, $first ? 1 : 2);
        $city = null;
        if ($withCity) {
            /** @var TestCityCard_01112b $city */
            $city = $world->placeCard(new TestCityCard_01112b(), Game::LOCATION_CITY_DOCKS, 0);
            $city->ControllerId = 0;
        }
        /** @var Action_01112b $action */
        $action = $carnaval->getActions()[1];
        return [$carnaval, $action, $city];
    }

    private function trigger(TestWorld $world, Action_01112b $action): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'implements IAbilityThatDependsOnNotBeingFirstPlayer so Lorenzo can offer' => function () {
                Assert::true(
                    (new Action_01112b()) instanceof IAbilityThatDependsOnNotBeingFirstPlayer,
                    'Lorenzo-compatible'
                );
            },

            'available when not first player and an uncontrolled discardable city card exists' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when first player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'first');
            },

            // WHY: OVERRIDE_AS_NOT_FIRST_PLAYER lets the actual first player take this branch (Lorenzo).
            'available to the first player when Lorenzo override is set' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true);
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'override');
            },

            'unavailable with no discardable city card in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'none');
            },

            // WHY: controlled city cards are not "available City Cards" for this discard.
            'unavailable when the only city card is controlled' => function () {
                $world = new TestWorld();
                [, $action, $city] = $this->scene($world, false);
                $city->ControllerId = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled');
            },

            'unavailable when the city card cannot be discarded from city' => function () {
                $world = new TestWorld();
                $carnaval = $world->placeCard(new _01112(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::FIRST_PLAYER, 2);
                $city = $world->placeCard(new TestCityCard_01112b(false), Game::LOCATION_CITY_DOCKS, 0);
                $city->ControllerId = 0;
                /** @var Action_01112b $action */
                $action = $carnaval->getActions()[1];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not discardable');
            },

            // WHY: faction Risks in city are not ICityDeckCard — must not enable the action.
            'unavailable when only a faction Risk sits at a city location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false, false);
                $world->placeCard(new _01107(), Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not city deck');
            },

            'unavailable when Carnaval is not in hand' => function () {
                $world = new TestWorld();
                [$carnaval, $action] = $this->scene($world, false);
                $carnaval->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01112 when targets exist' => function () {
                $world = new TestWorld();
                [$carnaval, $action] = $this->scene($world, false);

                $this->trigger($world, $action);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'prompt');
                Assert::same('01112', $transitions[0]->transition, 'name');
                Assert::same($carnaval->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
                Assert::same(1, $transitions[0]->playerId, 'player');
            },

            'trigger resolves immediately when no targets remain' => function () {
                $world = new TestWorld();
                [, $action, $city] = $this->scene($world, false);
                $city->ControllerId = 2;

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no prompt');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'args list only uncontrolled discardable city cards' => function () {
                $world = new TestWorld();
                [, $action, $city] = $this->scene($world, false);
                $controlled = $world->placeCard(new TestCityCard_01112b(), Game::LOCATION_CITY_FORUM, 2);
                $controlled->ControllerId = 2;
                $blocked = $world->placeCard(new TestCityCard_01112b(false), Game::LOCATION_CITY_BAZAAR, 0);
                $blocked->ControllerId = 0;

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01112,
                    'x'
                );

                Assert::same([$city->Id], $args['ids'], 'only the valid city card');
            },

            'act discards the city card as an effect and resolves' => function () {
                $world = new TestWorld();
                [$carnaval, $action, $city] = $this->scene($world, false);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01112,
                    'x',
                    $city->Id
                );

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'discarded');
                Assert::same($city->Id, $discards[0]->cardId, 'city card');
                Assert::same(Game::LOCATION_CITY_DOCKS, $discards[0]->fromLocation, 'from');
                Assert::same($carnaval->Id, $discards[0]->sourceId, 'Carnaval');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'act refuses a controlled city card' => function () {
                $world = new TestWorld();
                [, $action, $city] = $this->scene($world, false);
                $city->ControllerId = 2;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01112,
                        'x',
                        $city->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'controlled');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses a non-city-deck card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $risk = $world->placeCard(new _01107(), Game::LOCATION_CITY_DOCKS, 0);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01112,
                        'x',
                        $risk->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not city card');
            },
        ];
    }
}
