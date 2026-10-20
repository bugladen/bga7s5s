<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04046;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04046_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04046';
    }

    public function tests(): array
    {
        return [
            // WHY: The actor's round must be the opponent's. Your participant is the
            // duel opponent of that actor and must be a Duelist. Use pays from hand and
            // does not spend until the risk reaction fires. The threat is PENDING so the
            // finished round is not rewritten. Pass clears the participant and does not spend.
            'adds pending threat after the adversary round' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $risk = $world->placeCard(new _04046(), Game::LOCATION_HAND, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $duelist;
                $world->theah->duelOpponent = $duelist;
                /** @var Reaction_04046 $reaction */
                $reaction = $risk->getReactions()[0];

                $end = new EventDuelEndOfRound();
                $end->playerId = 2;
                $end->actorId = $foe->Id;
                $world->fireOn($risk, $end);
                Assert::same($duelist->Id, $reaction->participantId, 'your duelist');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::same(0, $reaction->participantId, 'cleared');

                $world->fireOn($risk, $end);
                $reaction->performReaction($world->game, 0, '', 'use');
                Assert::false($reaction->Used, 'pay has not resolved');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'in hand');

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                Assert::true($reaction->Used, 'spent');
                Assert::same(1, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT), 'challenger pending');
                Assert::same(0, $reaction->participantId, 'cleared after pay');

                $reaction->Used = false;
                $world->theah->duelChallengerIdOverride = $foe->Id;
                $world->fireOn($risk, $end);
                $world->fireOn($risk, $triggered);
                Assert::same(1, $world->game->globals->get(Game::PENDING_DEFENDER_THREAT), 'defender pending');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $end->playerId = 1;
                $world->fireOn($risk, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'your own round');

                $end->playerId = 2;
                $risk->Location = Game::LOCATION_DUELING_LINE;
                $world->fireOn($risk, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'left the hand');

                $risk->Location = Game::LOCATION_HAND;
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelOpponent = $hero;
                $world->fireOn($risk, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'participant is not a duelist');
            },
        ];
    }
}
