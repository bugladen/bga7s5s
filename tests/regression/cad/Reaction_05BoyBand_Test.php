<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AssertionFailedException;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05BoyBand;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05BoyBand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05BoyBand_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05BoyBand';
    }

    private function announced(int $playerId): EventCombatCardAnnounced
    {
        $event = new EventCombatCardAnnounced();
        $event->playerId = $playerId;
        return $event;
    }

    /**
     * Duel at the Docks: player 1's participant (challenger) vs player 2's foe (defender).
     *
     * @return array{0: TestWorld, 1: _05BoyBand, 2: Reaction_05BoyBand, 3: GenericCharacter, 4: GenericCharacter}
     */
    private function duel(): array
    {
        $world = new TestWorld();
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_ID, 7);
        $world->game->globals->set(Game::DUEL_ROUND, 1);
        $risk = $world->placeCard(new _05BoyBand(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $mine;
        $world->theah->duelOpponent = $foe;
        /** @var Reaction_05BoyBand $reaction */
        $reaction = $risk->getReactions()[0];

        return [$world, $risk, $reaction, $mine, $foe];
    }

    private function assertRejects(callable $fn, string $message): void
    {
        try
        {
            $fn();
        }
        catch (\Throwable $e)
        {
            return;
        }
        throw new AssertionFailedException($message . ' (expected an exception)');
    }

    public function tests(): array
    {
        return [
            // WHY: Adversary announces a combat card. Offer, pick the Thug/Duelist before paying,
            // then swap + threat on both sides only after the pay step triggers the reaction.
            'swaps the participant with a Thug or Duelist and adds a threat to both' => function () {
                [$world, $risk, $reaction, $mine, $foe] = $this->duel();
                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Bard', ['Bard']), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->announced(2));
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
                Assert::same($mine->Id, $reaction->participantId, 'participant');

                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_map(fn(array $b) => $b['reaction'], $buttons);
                Assert::true(in_array("character-{$thug->Id}", $ids, true), 'thug button');
                Assert::true(in_array("character-{$duelist->Id}", $ids, true), 'duelist button');
                Assert::true(in_array('pass', $ids, true), 'pass button');
                Assert::false(in_array('character-' . $mine->Id, $ids, true), 'not self');
                Assert::count(2, array_filter($ids, fn(string $id) => str_starts_with($id, 'character-')), 'only Thug + Duelist');

                $reaction->performReaction($world->game, 0, '', 'character-' . $thug->Id);
                Assert::same($thug->Id, $reaction->chosenSwapId, 'stored before pay');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');
                Assert::count(0, $world->theah->swappedParticipants, 'no swap before pay');
                Assert::false($reaction->Used, 'not spent before pay');

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'character-' . $thug->Id;
                $world->fireOn($risk, $triggered);

                Assert::count(1, $world->theah->swappedParticipants, 'swapped');
                Assert::same($mine->Id, $world->theah->swappedParticipants[0]['oldId'], 'old participant');
                Assert::same($thug->Id, $world->theah->swappedParticipants[0]['newId'], 'new participant');
                Assert::same(7, $world->theah->swappedParticipants[0]['duelId'], 'duel');
                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'threat');
                Assert::same(1, $threat[0]->challengerThreat, 'challenger threat');
                Assert::same(1, $threat[0]->defenderThreat, 'defender threat');
                Assert::true($reaction->Used, 'spent');
            },

            // WHY: Pass leaves the Risk in hand for a later announcement.
            'pass clears the offer without spending' => function () {
                [$world, $risk, $reaction] = $this->duel();
                $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->announced(2));
                $reaction->performReaction($world->game, 0, '', 'pass');

                Assert::false($reaction->Used, 'pass');
                Assert::same(0, $reaction->participantId, 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },

            'does not offer for your own card, outside a duel, or from outside the hand' => function () {
                [$world, $risk, $reaction] = $this->duel();
                $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->announced(1));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'your own card');

                $world->game->globals->set(Game::IN_DUEL, false);
                $world->fireOn($risk, $this->announced(2));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no duel');

                $world->game->globals->set(Game::IN_DUEL, true);
                $risk->Location = Game::LOCATION_PURGATORY;
                $world->fireOn($risk, $this->announced(2));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'Risk not in hand');
            },

            // WHY: Only your own Thug/Duelist at the participant's location is a legal partner.
            'does not offer without a Thug or Duelist at the duel location' => function () {
                [$world, $risk] = $this->duel();
                $world->placeCharacter(new GenericCharacter('Bard', ['Bard']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Faraway', ['Thug']), Game::LOCATION_CITY_FORUM, 1);
                $world->placeCharacter(new GenericCharacter('Enemy Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($risk, $this->announced(2));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no legal partner');
            },

            // WHY: swapParticipantsInDuel refuses a Harpooned participant; hide the offer and
            // reject the pick before the player pays.
            'does not offer or accept when the participant is Harpooned' => function () {
                [$world, $risk, $reaction, $mine] = $this->duel();
                $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->announced(2));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered first');

                $mine->addCondition(Game::HARPOON_CONDITION);
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $this->announced(2));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'harpooned');

                $this->assertRejects(
                    fn() => $reaction->performReaction($world->game, 0, '', 'character-' . $thug->Id),
                    'rejects the pick'
                );
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },

            'rejects a swap partner that is not an eligible Thug or Duelist' => function () {
                [$world, $risk, $reaction, $mine, $foe] = $this->duel();
                $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
                $bard = $world->placeCharacter(new GenericCharacter('Bard', ['Bard']), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($risk, $this->announced(2));

                foreach ([$bard, $foe, $mine] as $bad)
                {
                    $this->assertRejects(
                        fn() => $reaction->performReaction($world->game, 0, '', 'character-' . $bad->Id),
                        'rejects ' . $bad->Name
                    );
                }
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
            },
        ];
    }
}
