<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03008;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03008_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03008';
    }

    /**
     * @return array{0:_03008,1:Action_03008,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03008(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Proud'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Influence = 2;
        $performer->ModifiedInfluence = 2;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Influence = 2;
        $foe->ModifiedInfluence = 2;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_03008 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'is a city action that must pick a performer' => function () {
                $action = new Action_03008();
                Assert::instanceOf(RiskCityAction::class, $action, 'city');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Print has no Engage cost. canChallenge does not look at Engaged, and this
            // action never adds that gate, so an engaged performer stays legal.
            'available for an engaged city character facing equal or lower Influence' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged performer');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performer');

                $foe->ModifiedInfluence = 3;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'higher Influence');
                $foe->ModifiedInfluence = 1;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'lower Influence');

                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'rejects your own character, an uncontrolled character, another location, and higher Influence' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'equal');

                [$self] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($self, 'self');

                $foe->ControllerId = 0;
                [$crowd] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($crowd, 'uncontrolled');
                $foe->ControllerId = 2;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($away, 'location');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                $foe->ModifiedInfluence = 9;
                [$high] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($high, 'higher');
            },

            'trigger sets the Arrogant Combat challenge and does not engage' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::ARROGANT_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03008', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'not available outside the hand unless overridden' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = 'Discard-1';
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'discard');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },
        ];
    }
}
