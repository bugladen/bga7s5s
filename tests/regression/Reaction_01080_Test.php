<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01080;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01080;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01080_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01080';
    }

    /**
     * Mid-duel scene. The harness getDuelOpponentId() always returns duelOpponent, so to make "the opponent of the
     * dying character" be OUR participant the enemy is the round actor and our participant is the duel opponent.
     *
     * @return array{0:_01080,1:Reaction_01080,2:Character,3:Character} risk, reaction, our participant, enemy
     */
    private function duel(TestWorld $world, string $riskLocation = Game::LOCATION_HAND): array
    {
        $risk = $world->placeCard(new _01080(), $riskLocation, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $enemy;
        $world->theah->duelOpponent = $mine;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var Reaction_01080 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $mine, $enemy];
    }

    private function destroyed(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    private function duelEnd(TestWorld $world): EventDuelEnd
    {
        $event = new EventDuelEnd();
        $event->theah = $world->theah;
        return $event;
    }

    /** Arm the reaction (enemy destroyed in duel) and drop the queued events. */
    private function arm(TestWorld $world, Reaction_01080 $reaction, Character $enemy): void
    {
        $reaction->handleEvent($this->destroyed($world, $enemy->Id));
        $world->theah->takeQueuedEvents();
    }

    private function triggered(TestWorld $world, Reaction_01080 $reaction): EventRiskReactionTriggered
    {
        $event = new EventRiskReactionTriggered();
        $event->internalId = $reaction->Id;
        $event->reactionId = 'pressure';
        $event->theah = $world->theah;
        return $event;
    }

    private function wasOffered(TestWorld $world, Reaction_01080 $reaction): bool
    {
        foreach ($world->theah->queuedOfType(EventTransition::class) as $t) {
            if ($t->internalId === $reaction->Id) {
                return true;
            }
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'EventDuelEnd offers the reaction after an enemy was destroyed in the duel' => function () {
                $world = new TestWorld();
                [$risk, $reaction, , $enemy] = $this->duel($world);
                $reaction->handleEvent($this->destroyed($world, $enemy->Id));
                Assert::count(0, $world->theah->queuedEvents, 'arming alone queues nothing');

                $reaction->handleEvent($this->duelEnd($world));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered once');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'owner');
            },

            'EventDuelEnd does not offer when nothing was destroyed' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'not armed');
            },

            // WHY: this is the only gate against re-offering; Reaction_01080 itself never calls setUsed.
            'EventDuelEnd does not offer once the reaction is used' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);
                $reaction->Used = true;

                $reaction->handleEvent($this->duelEnd($world));

                Assert::false($reaction->isAvailable(), 'isAvailable gate');
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: Risk reactions are played from hand; once it has left hand (discarded etc.) it can no longer be offered.
            'does not arm when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world, Game::LOCATION_CITY_FORUM);
                $reaction->handleEvent($this->destroyed($world, $enemy->Id));
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer if the Risk leaves hand between arming and duel end' => function () {
                $world = new TestWorld();
                [$risk, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);
                $risk->Location = Game::LOCATION_CITY_FORUM;

                $reaction->handleEvent($this->duelEnd($world));

                Assert::count(0, $world->theah->queuedEvents, 'left hand');
            },

            'does not arm when not in a duel' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $reaction->handleEvent($this->destroyed($world, $enemy->Id));
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'outside duel');
            },

            'does not arm when the destroyed character is our own participant' => function () {
                $world = new TestWorld();
                [, $reaction, $mine] = $this->duel($world);
                $reaction->handleEvent($this->destroyed($world, $mine->Id));
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'our own loss');
            },

            'does not arm when the destroyed character is not in the duel' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_FORUM, 2);
                $reaction->handleEvent($this->destroyed($world, $bystander->Id));
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'bystander');
            },

            'does not arm when the owner has no participant in the duel' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $enemy] = $this->duel($world);
                // Both duelists belong to someone else (player 3) from the owner's point of view.
                $mine->ControllerId = 3;
                $reaction->handleEvent($this->destroyed($world, $enemy->Id));
                $reaction->handleEvent($this->duelEnd($world));
                Assert::count(0, $world->theah->queuedEvents, 'spectator');
            },

            'EventPlayerTurnEnd disarms so a later duel end does not re-offer' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $turnEnd = new EventPlayerTurnEnd();
                $turnEnd->theah = $world->theah;
                $reaction->handleEvent($turnEnd);
                $reaction->handleEvent($this->duelEnd($world));

                Assert::count(0, $world->theah->queuedEvents, 'cleared at end of turn');
            },

            'buttons offer Pressure <location> and Pass' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $buttons = $reaction->getReactionButtonProperties($world->theah);

                $ids = array_map(fn($b) => $b['reaction'], $buttons);
                Assert::same(['pressure', 'pass'], $ids, 'buttons');
                Assert::contains(Game::LOCATION_CITY_DOCKS, $buttons[0]['text'], 'names the dying character\'s location');
                Assert::contains('Faction Hand', $reaction->getReactionDescription($world->theah), 'risk reaction prefix');
            },

            'pressure choice enters the pay state then the pay transition and ends with done' => function () {
                $world = new TestWorld();
                [$risk, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pressure');

                $events = $world->theah->queuedEvents;
                // CardReaction::performReaction stacks a ReactionActivated at the FRONT, then pay-state + pay transition follow.
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay state');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'in-hand reaction pay');
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same($reaction->Id, $pay[0]->internalId, 'internal id');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'pay transition');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::true(count($events) >= 3, 'activated + pay state + pay transition');
            },

            'pass queues nothing beyond done' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: "Pressure your performer's location" - the performer is OUR duel participant; the location is where the
            // destroyed enemy was (captured at destroy time, since the enemy has left play by the time the reaction fires).
            'RiskReactionTriggered pressures the dying character\'s location with Influence for our participant' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);
                $world->game->pressureLocationResult = [true, 'ok', 2];

                $reaction->handleEvent($this->triggered($world, $reaction));

                $occurring = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $occurring, 'pressure occurring');
                Assert::same($mine->Id, $occurring[0]->performerId, 'performer is our participant');
                Assert::same(Game::LOCATION_CITY_DOCKS, $occurring[0]->location, 'location');
                Assert::true(in_array(Game::STAT_INFLUENCE, $occurring[0]->pressureTypes, true), 'Influence pressure');

                Assert::count(1, $world->game->pressureLocationCalls, 'resolved once');
                Assert::same($mine->Id, $world->game->pressureLocationCalls[0]['performerId'], 'calc performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->pressureLocationCalls[0]['location'], 'calc location');
                Assert::same(Game::STAT_INFLUENCE, $world->game->pressureLocationCalls[0]['pressureType'], 'Influence');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal pressure');

                $pressured = $world->theah->queuedOfType(EventLocationPressured::class);
                Assert::count(1, $pressured, 'pressured result');
                Assert::same($reaction->Id, $pressured[0]->abilityId, 'tagged with the reaction so only its success can claim');
                Assert::true($pressured[0]->success, 'success');
            },

            'RiskReactionTriggered for another reaction does nothing' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $event = $this->triggered($world, $reaction);
                $event->internalId = 'someOtherReaction';
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::count(0, $world->game->pressureLocationCalls, 'no pressure');
            },

            'RiskReactionTriggered with a non-pressure reactionId does nothing' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $event = $this->triggered($world, $reaction);
                $event->reactionId = 'pass';
                $reaction->handleEvent($event);

                Assert::count(0, $world->game->pressureLocationCalls, 'no pressure');
            },

            'successful pressure result claims the location for our participant\'s controller' => function () {
                $world = new TestWorld();
                [, $reaction, $mine, $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(1, $claims[0]->playerId, 'claimer');
                Assert::same($mine->Id, $claims[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'location');
            },

            'failed pressure result does not claim' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
            },

            // WHY: another ability's pressure at the same time must not trigger this reaction's claim.
            'pressure result from a different ability does not claim' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);

                $result = new EventLocationPressureResult();
                $result->abilityId = 'someOtherAbility';
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
            },

            'successful pressure on a location that cannot be claimed announces instead of claiming' => function () {
                $world = new TestWorld();
                [, $reaction, , $enemy] = $this->duel($world);
                $this->arm($world, $reaction, $enemy);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                $messagesBefore = count($world->game->notify->messages);

                $result = new EventLocationPressureResult();
                $result->abilityId = $reaction->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $reaction->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count($messagesBefore + 1, $world->game->notify->messages, 'players are told it cannot be claimed');
                Assert::contains('cannot be claimed', $world->game->notify->messages[$messagesBefore]['message'], 'message');
            },
        ];
    }
}
