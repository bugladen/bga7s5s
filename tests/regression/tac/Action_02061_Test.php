<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02061_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02061';
    }

    /**
     * @return array{0:_02061,1:Action_02061,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02061(), Game::LOCATION_HAND, 1);
        $duelist = $world->placeCharacter(new GenericCharacter('Blade', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_02061 $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
        return [$risk, $action, $duelist, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Duelist city action that must pick a performer' => function () {
                $action = new Action_02061();
                Assert::instanceOf(RiskCityAction::class, $action, 'city action');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when an unengaged Duelist shares a city location with an opposing non-Leader' => function () {
                $world = new TestWorld();
                [, $action, $duelist, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one performer');
                Assert::same($duelist->Id, $performers[0]->Id, 'duelist');

                $duelist->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged duelist');
                $duelist->Engaged = false;

                $duelist->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home is not the city');
                $duelist->Location = Game::LOCATION_CITY_DOCKS;

                $foe->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opponent');
            },

            'a Leader is not a target, and a non-Duelist cannot perform' => function () {
                $world = new TestWorld();
                [, $action, $duelist, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $leader = $world->placeCharacter(new GenericLeader('Captain'), Game::LOCATION_CITY_DOCKS, 2);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 1);

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only a Leader stands there');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::false(in_array($civilian->Id, $ids, true), 'civilian');
                Assert::false(in_array($duelist->Id, $ids, true), 'duelist has no legal foe');
                unset($leader);
            },

            'not available outside the hand unless the hand check is overridden' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = 'Discard-1';
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'discard');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },

            'rejects your own character, a different location, and a Leader' => function () {
                $world = new TestWorld();
                [, $action, $duelist, $foe] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing non-leader');

                [$own] = $action->isValidTargetForAbility($world->game, $duelist);
                Assert::false($own, 'own controller');

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($away, 'location');

                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                [$noLeader] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($noLeader, 'leader');
            },

            'trigger sets an unrefusable Combat challenge and opens 02061' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02061', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            // WHY: Starter decks ship two copies. CHALLENGE_TYPE is table-global, so the
            // spare copy must not also add threat. The gate is this action's id in CHOSEN_ACTION.
            'first-round threat adds +1/+1 only for the chosen copy' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $spare = $world->placeCard(new _02061(), 'Discard-1', 2);
                /** @var Action_02061 $spareAction */
                $spareAction = $spare->getActions()[0];
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE);
                $world->game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $threat = new EventGenerateChallengeThreat();
                $threat->actorThreat = 2;
                $threat->adversaryThreat = 2;
                $threat->theah = $world->theah;
                $action->handleEvent($threat);
                Assert::same(3, $threat->actorThreat, 'actor');
                Assert::same(3, $threat->adversaryThreat, 'adversary');
                Assert::contains($risk->getInjectCode(), $threat->explanations[0], 'explanation');

                $spareThreat = new EventGenerateChallengeThreat();
                $spareThreat->actorThreat = 2;
                $spareThreat->adversaryThreat = 2;
                $spareThreat->theah = $world->theah;
                $spareAction->handleEvent($spareThreat);
                Assert::same(2, $spareThreat->actorThreat, 'spare copy does not add');
                Assert::same(2, $spareThreat->adversaryThreat, 'spare adversary');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $other = new EventGenerateChallengeThreat();
                $other->actorThreat = 2;
                $other->adversaryThreat = 2;
                $other->theah = $world->theah;
                $action->handleEvent($other);
                Assert::same(2, $other->actorThreat, 'other challenge type');
            },
        ];
    }
}
