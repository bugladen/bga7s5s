<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatDependsOnNotBeingFirstPlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Keelhaul;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Keelhaulb;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Keelhaulb_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Keelhaulb';
    }

    /**
     * Risk in hand (player 1), performer + foe at uncontrolled Docks. Player 1 is not first.
     *
     * @return array{0:_05Keelhaul,1:Action_05Keelhaulb,2:GenericCharacter,3:GenericCharacter,4:TestWorld}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _05Keelhaul(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::FIRST_PLAYER, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_05Keelhaulb $action */
        $action = $risk->getActions()[1];
        return [$risk, $action, $performer, $foe, $world];
    }

    public function tests(): array
    {
        return [
            'is a Covert RiskAction that depends on not being first player' => function () {
                $action = new Action_05Keelhaulb();
                Assert::instanceOf(RiskAction::class, $action, 'not city');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::instanceOf(IAbilityThatDependsOnNotBeingFirstPlayer::class, $action, 'lorenzo');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Bullet-If hides the Action when you are first player. Lorenzo's
            // OVERRIDE_AS_NOT_FIRST_PLAYER restores offer. Covert + Dashed Finesse +
            // engaged-still-ok same as the Pirate half.
            'is only offered when you are not first player at an uncontrolled city location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'not first');

                $world->game->globals->set(Game::FIRST_PLAYER, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'is first');

                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'lorenzo override');
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
                $world->game->globals->set(Game::FIRST_PLAYER, 2);

                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged still ok');
                $performer->Engaged = false;

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                $performer->DashedFinesse = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed finesse');
                $performer->DashedFinesse = false;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposing');
            },

            'rejects your own character, an uncontrolled character, and another location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);

                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing');

                [$self] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($self, 'self');

                $foe->ControllerId = 0;
                [$crowd] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($crowd, 'uncontrolled');
                $foe->ControllerId = 2;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($away, 'location');
            },

            'trigger sets the Keelhaul Finesse challenge and does not engage' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::KEELHAUL_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('05Keelhaulb', $transitions[0]->transition, 'name');
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
