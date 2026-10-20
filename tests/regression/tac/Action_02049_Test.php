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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02049_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02049';
    }

    /**
     * @return array{0:_02049,1:Action_02049,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, array $traits = ['Mercenary']): array
    {
        $risk = $world->placeCard(new _02049(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Valeri'), Game::LOCATION_CITY_DOCKS, 1);
        $target = $world->placeCharacter(new GenericCharacter('Mark', $traits), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_02049 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $target];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that targets characters and requires a performer' => function () {
                $action = new Action_02049();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when you have a character who can challenge an opposing Mercenary or Thug' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, ['Thug']);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'thug');
            },

            // WHY: canChallenge is "controlled", so an engaged character can still open this action.
            'an engaged challenger still qualifies' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable without a legal adversary, when the risk is not in hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $target] = $this->scene($world, ['Noble']);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not merc or thug');

                $target->addTrait($world->game, 'Mercenary');
                $target->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'different location');

                $target->Location = Game::LOCATION_CITY_DOCKS;
                $performer->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot challenge');

                $performer->ControllerId = 1;
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'a Mercenary or Thug at the performer location is a legal target' => function () {
                $world = new TestWorld();
                [, $action, , $merc] = $this->scene($world, ['Mercenary']);
                [$ok] = $action->isValidTargetForAbility($world->game, $merc);
                Assert::true($ok, 'mercenary');

                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 2);
                [$okThug] = $action->isValidTargetForAbility($world->game, $thug);
                Assert::true($okThug, 'thug');
            },

            // WHY: The click check does not require the target to be controlled. The availability scan does.
            'your own character, a different location, and a non-Mercenary are illegal' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                [$own] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($own, 'own');

                $away = $world->placeCharacter(new GenericCharacter('Away', ['Thug']), Game::LOCATION_CITY_FORUM, 2);
                [$awayOk] = $action->isValidTargetForAbility($world->game, $away);
                Assert::false($awayOk, 'away');

                $noble = $world->placeCharacter(new GenericCharacter('Noble', ['Noble']), Game::LOCATION_CITY_DOCKS, 2);
                [$nobleOk] = $action->isValidTargetForAbility($world->game, $noble);
                Assert::false($nobleOk, 'noble');
            },

            'an uncontrolled Thug is still a legal click target' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $thug = $world->placeCharacter(new GenericCharacter('Loose', ['Thug']), Game::LOCATION_CITY_DOCKS, 0);
                $thug->ControllerId = 0;
                [$ok] = $action->isValidTargetForAbility($world->game, $thug);
                Assert::true($ok, 'uncontrolled');
            },

            'trigger sets a Combat challenge and does not resolve the action yet' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same(Game::JUSTICE_SERVED_COLD_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type 16');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02049', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'waits for the challenge');
            },

            'ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_TYPE), 'unset');
            },
        ];
    }
}
