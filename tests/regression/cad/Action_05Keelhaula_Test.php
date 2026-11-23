<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Keelhaul;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Keelhaula;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Keelhaula_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Keelhaula';
    }

    /**
     * Risk in hand (player 1), Pirate performer + foe at uncontrolled Docks.
     *
     * @return array{0:_05Keelhaul,1:Action_05Keelhaula,2:GenericCharacter,3:GenericCharacter,4:TestWorld}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _05Keelhaul(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Pirate'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->addTrait($world->game, 'Pirate', true);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_05Keelhaula $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe, $world];
    }

    public function tests(): array
    {
        return [
            'is a Castille risk with dual Covert challenge actions' => function () {
                $card = new _05Keelhaul();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'Cesca');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(0, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::count(2, $card->getActions(), 'two actions');
                Assert::instanceOf(RiskAction::class, $card->getActions()[0], 'not city');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $card->getActions()[0], 'targets');
            },

            // WHY: Covert = uncontrolled City. Pirate heading trait. No Engage cost —
            // engaged Pirates stay legal. Dashed Combat blocks a Combat challenge.
            'is only offered to a Pirate at an uncontrolled city location with an opposing character' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged still ok');
                $performer->Engaged = false;

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                $performer->DashedCombat = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed combat');
                $performer->DashedCombat = false;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposing');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                $nonPirate = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], array_values($ids), 'Pirate only');
                Assert::false(in_array($nonPirate->Id, $ids, true), 'non-Pirate excluded');
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

            'trigger sets the Keelhaul Pirate Combat challenge and does not engage' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::KEELHAUL_PIRATE_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('05Keelhaula', $transitions[0]->transition, 'name');
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
