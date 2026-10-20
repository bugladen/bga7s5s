<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01132;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01132;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;

class Action_01132_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01132';
    }

    /**
     * Sorcerer performer at Docks; Risk in hand.
     *
     * @return array{0:_01132,1:Action_01132,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01132(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Action_01132 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function trigger(TestWorld $world, Action_01132 $action, int $performerId): void
    {
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performerId);
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction Sorcerer ability that needs a performer' => function () {
                $action = new Action_01132();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a city Sorcerer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Sorcerer in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = [];
                $performer->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'unavailable when the only Sorcerer is at Home' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                // WHY: RiskCityAction requires a city character; Action_01132 further requires Sorcerer
                // among city characters — Home Sorcerer alone fails the City Action gate.
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home only');
            },

            'performers are only Sorcerers in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'Sorcerer only');
                Assert::false(in_array($plain->Id, $ids, true), 'plain excluded');
            },

            'trigger moves engaged Home, engages remaining, and fires Sorcerer + ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $engagedFoe = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 2);
                $engagedFoe->Engaged = true;
                $unengagedAlly = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 1);

                $this->trigger($world, $action, $performer->Id);

                $queued = $world->theah->queuedEvents;
                Assert::instanceOf(EventSorcererAbilityStart::class, $queued[0], 'sorcery start first');
                Assert::same($risk->Id, $queued[0]->sourceId, 'start source');
                Assert::same($performer->Id, $queued[0]->performerId, 'start performer');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($engagedFoe->Id, $moves[0]->cardId, 'engaged moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from Docks');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to Home');
                Assert::false($moves[0]->engage, 'no engage on move');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                $engageIds = array_map(fn($e) => $e->cardId, $engages);
                Assert::true(in_array($performer->Id, $engageIds, true), 'performer engaged');
                Assert::true(in_array($unengagedAlly->Id, $engageIds, true), 'ready engaged');
                Assert::false(in_array($engagedFoe->Id, $engageIds, true), 'already engaged not re-engaged');

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'sorcery played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger with no characters at the location still fires Sorcerer and ActionResolved' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_CITY_FORUM;
                // Leave Docks empty; performer is chosen but relocated before trigger reads location.
                $this->trigger($world, $action, $performer->Id);

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'performer alone engaged');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
