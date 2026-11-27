<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Stash;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Stash_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Stash';
    }

    /**
     * @return array{
     *   world: TestWorld,
     *   risk: Risk,
     *   reaction: Reaction_05Stash,
     *   mine: GenericCharacter,
     *   engaged: GenericCharacter
     * }
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $risk = $world->placeCard(new _05Stash(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $engaged = $world->placeCharacter(new GenericCharacter('Tired'), Game::LOCATION_CITY_DOCKS, 2);
        $engaged->Engaged = true;
        /** @var Reaction_05Stash $reaction */
        $reaction = $risk->getReactions()[0];

        return compact('world', 'risk', 'reaction', 'mine', 'engaged');
    }

    private function failedPressure(int $playerId, string $location): EventLocationPressureResult
    {
        $event = new EventLocationPressureResult();
        $event->playerId = $playerId;
        $event->location = $location;
        $event->success = false;
        $event->pressureType = Game::STAT_INFLUENCE;
        $event->totalsExplanation = '0 vs 1';
        $event->highDramaBasicAction = true;
        $event->abilityId = '';
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a Castille Risk with Revelry, City Reaction, Covert Maneuver, and Cesca' => function () {
                $card = new _05Stash();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $card, 'Cesca');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(0, $card->Riposte, 'riposte');
                Assert::same(3, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true(in_array('Revelry', $card->Traits, true), 'Revelry');
                Assert::count(1, $card->getReactions(), 'one reaction');
                Assert::count(1, $card->getManeuvers(), 'one maneuver');
                Assert::instanceOf(RiskReaction::class, $card->getReactions()[0], 'RiskReaction');
            },

            // WHY: Your failed pressure + city presence + Engaged character at that location.
            // Engarde = createCardEngardedEvent (Serenissima). Ready characters are not targets.
            'offers on your failed pressure, then engardes the chosen Engaged character after pay' => function () {
                ['world' => $world, 'risk' => $risk, 'reaction' => $reaction, 'engaged' => $engaged] = $this->scene();

                $world->fireOn($risk, $this->failedPressure(1, Game::LOCATION_CITY_DOCKS));
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
                Assert::same(Game::LOCATION_CITY_DOCKS, $reaction->pressureLocation, 'location saved');

                $ready = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 2);
                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_map(fn(array $b) => $b['reaction'], $buttons);
                Assert::true(in_array('use', $ids, true), 'use');
                Assert::true(in_array('pass', $ids, true), 'pass');

                $reaction->performReaction($world->game, 0, '', 'use');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');
                Assert::false($reaction->Used, 'not spent before pay');

                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                Assert::same('chooseCharacter', $reaction->stage, 'choose');

                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_map(fn(array $b) => $b['reaction'], $buttons);
                Assert::true(in_array("character-{$engaged->Id}", $ids, true), 'engaged target');
                Assert::false(in_array("character-{$ready->Id}", $ids, true), 'ready not engardeable');

                $reaction->performReaction($world->game, 0, '', 'character-' . $engaged->Id);
                // Pick queues ONLY CharacterTargeted; Used already (setUsed at pick); no engarde yet.
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($engaged->Id, $targeted[0]->targetId, 'target');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no engarde yet');
                Assert::true($reaction->Used, 'spent at pick');
                Assert::same($engaged->Id, $reaction->chosenTargetId, 'target kept for the hook');

                // Survive (hook delivered AFTER setUsed — must not be behind isAvailable): engarde, batch shared.
                $hook = new EventCharacterTargeted();
                $hook->abilityId = $reaction->Id;
                $hook->targetId = $engaged->Id;
                $hook->batchId = 77;
                $hook->canceled = false;
                $world->fireOn($risk, $hook);
                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::same($engaged->Id, $engarde[0]->cardId, 'engarde');
                Assert::same(77, $engarde[0]->batchId, 'engarde batch');
                Assert::same('', $reaction->stage, 'reset');
                Assert::same(0, $reaction->chosenTargetId, 'sticky target cleared');
                Assert::same('', $reaction->pressureLocation, 'location cleared');
            },

            'canceled targeting engardes nothing and clears state; foreign ability ignored' => function () {
                ['world' => $world, 'risk' => $risk, 'reaction' => $reaction, 'engaged' => $engaged] = $this->scene();
                $world->fireOn($risk, $this->failedPressure(1, Game::LOCATION_CITY_DOCKS));
                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $world->fireOn($risk, $triggered);
                $reaction->performReaction($world->game, 0, '', 'character-' . $engaged->Id);
                Assert::same($engaged->Id, $reaction->chosenTargetId, 'target stored');
                $world->theah->takeQueuedEvents();

                $foreign = new EventCharacterTargeted();
                $foreign->abilityId = 'other';
                $foreign->targetId = $engaged->Id;
                $foreign->canceled = false;
                $world->fireOn($risk, $foreign);
                Assert::count(0, $world->theah->queuedEvents, 'foreign ignored');
                Assert::same($engaged->Id, $reaction->chosenTargetId, 'foreign leaves state');

                $hook = new EventCharacterTargeted();
                $hook->abilityId = $reaction->Id;
                $hook->targetId = $engaged->Id;
                $hook->canceled = true;
                $world->fireOn($risk, $hook);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no engarde');
                Assert::same(0, $reaction->chosenTargetId, 'cleared on cancel');
                Assert::same('', $reaction->pressureLocation, 'location cleared on cancel');
            },

            'hides on success, opponent pressure, empty city, or no Engaged targets' => function () {
                ['world' => $world, 'risk' => $risk, 'reaction' => $reaction, 'engaged' => $engaged, 'mine' => $mine] = $this->scene();

                $success = $this->failedPressure(1, Game::LOCATION_CITY_DOCKS);
                $success->success = true;
                $world->fireOn($risk, $success);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'success');

                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $this->failedPressure(2, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'opponent');

                $world->theah->takeQueuedEvents();
                $engaged->Engaged = false;
                $world->fireOn($risk, $this->failedPressure(1, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no Engaged');
                $engaged->Engaged = true;

                $mine->Location = Game::LOCATION_PLAYER_HOME;
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $this->failedPressure(1, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no city presence');
            },
        ];
    }
}
