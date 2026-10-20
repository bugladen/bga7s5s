<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04009;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04009_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04009';
    }

    public function tests(): array
    {
        return [
            'is an en garde risk action' => function () {
                $action = new Action_04009();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: One valid opponent skips the opponent button and goes straight to their
            // character. The challenge type stays off the auto-engage list. A Duelist defender
            // arms +1 Riposte on the first combat card only, and a resolved action outside a
            // duel clears an unused arm.
            'a single opponent is forced to challenge, and a duelist keeps the first riposte' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04009(), Game::LOCATION_HAND, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04009 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');
                $defender->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $defender->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $defender->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04009_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'skipped opponent pick');
                Assert::same($defender->Id, $action->DefenderId, 'stashed before chosen performer is overwritten');

                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04009_2, '', $foe->Id);
                Assert::same(Game::RATTLE_THE_RIGGING_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'custom type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'enemy is the challenger');
                Assert::same($defender->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'duelist is the target');
                Assert::same($defender->Id, $action->FirstCombatCardRiposteCharacterId, 'riposte armed');
                Assert::same('04009_3', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'issue');

                $calc = new EventDuelCalculateCombatCardStats();
                $calc->actorId = $defender->Id;
                $calc->theah = $world->theah;
                $action->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'plus one');
                Assert::same(0, $action->FirstCombatCardRiposteCharacterId, 'spent on the first card');

                $action->FirstCombatCardRiposteCharacterId = $defender->Id;
                $world->game->globals->set(Game::IN_DUEL, true);
                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $action->handleEvent($resolved);
                Assert::same($defender->Id, $action->FirstCombatCardRiposteCharacterId, 'mid-duel resolve keeps it');
                $world->game->globals->set(Game::IN_DUEL, false);
                $action->handleEvent($resolved);
                Assert::same(0, $action->FirstCombatCardRiposteCharacterId, 'outside a duel it clears');

                $action->FirstCombatCardRiposteCharacterId = $defender->Id;
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $action->handleEvent($end);
                Assert::same(0, $action->FirstCombatCardRiposteCharacterId, 'duel end');
            },

            'two opponents open the opponent picker first' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04009(), Game::LOCATION_HAND, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 3);
                /** @var Action_04009 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $defender->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04009', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick an opponent');
                Assert::same(0, $action->FirstCombatCardRiposteCharacterId, 'not a duelist');
            },
        ];
    }
}
