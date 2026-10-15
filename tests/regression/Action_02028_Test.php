<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02028_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02028';
    }

    /**
     * Diplomat + opposing Influence>=1 at Docks; risk in hand.
     *
     * @return array{0:_02028,1:Action_02028,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02028(), Game::LOCATION_HAND, 1);
        $diplomat = $world->placeCharacter(
            new GenericCharacter('Dip', ['Diplomat']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->ModifiedInfluence = 1;
        /** @var Action_02028 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $diplomat, $foe];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets characters and requires performer' => function () {
                $action = new Action_02028();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with Diplomat who can challenge and opposing Influence >= 1' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Diplomat trait' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $diplomat->ModifiedTraits = ['Mercenary'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Diplomat');
            },

            'unavailable when Diplomat has DashedInfluence' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $diplomat->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed');
            },

            'unavailable when opposing Influence is below 1' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Inf');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are only Diplomats with eligible opposing targets' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Forum Foe'), Game::LOCATION_CITY_FORUM, 2);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$diplomat->Id], $ids, 'Diplomat only');
                Assert::false(in_array($crew->Id, $ids, true), 'no crew');
            },

            'isValidTarget rejects own characters' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ok, 'own');
            },

            'isValidTarget rejects wrong location' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);
                $elsewhere->ModifiedInfluence = 2;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $elsewhere);
                Assert::false($ok, 'wrong loc');
            },

            'isValidTarget rejects Influence below 1' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 0;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($ok, 'Inf < 1');
            },

            'isValidTarget accepts opposing Influence >= 1 at same location' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'valid');
            },

            // On trigger: CHALLENGE_STAT=STAT_INFLUENCE, CHALLENGE_TYPE=BATTLE_OF_WITS (14),
            // transition "02028". No createActionResolvedEvent on trigger (by design).
            'trigger sets Influence challenge type 14 and queues transition 02028' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(
                    Game::BATTLE_OF_WITS_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'type 14'
                );
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02028', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolved');
            },
        ];
    }
}
