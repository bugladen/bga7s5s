<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03039_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03039';
    }

    /**
     * @return array{0:_03039,1:Technique_03039}
     */
    private function scene(TestWorld $world): array
    {
        $inigo = $world->placeCharacter(new _03039(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new \Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $inigo;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->currentRoundThrust = 2;
        /** @var Technique_03039 $technique */
        $technique = $inigo->getTechniques()[0];
        return [$inigo, $technique];
    }

    public function tests(): array
    {
        return [
            // WHY: Combat card needs at least 2 Thrust. Íñigo must be the actor.
            'needs Íñigo as the actor and two Thrust' => function () {
                $world = new TestWorld();
                [$inigo, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
                $world->theah->currentRoundThrust = 1;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'one thrust');
                $world->theah->currentRoundThrust = 2;
                $world->theah->duelActor = $world->theah->duelOpponent;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->theah->duelActor = $inigo;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');
            },

            // WHY: Move Home is armed on resolve even when the hand is empty. En Garde
            // compares hands AFTER the discard leaves, so the discarded card is excluded.
            // The Home move does not engage.
            'an empty hand still moves him Home, and a larger hand en gardes him' => function () {
                $world = new TestWorld();
                [$inigo, $technique] = $this->scene($world);
                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no discard');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), '0 is not more than 0');
                Assert::true($technique->Used, 'spent');

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'no engage');

                $world->theah->takeQueuedEvents();
                $inigo->Location = Game::LOCATION_PLAYER_HOME;
                $technique->handleEvent($resolve);
                $technique->handleEvent($end);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'already home');
            },

            'the discard chooser arms En Garde for the hand that remains' => function () {
                $world = new TestWorld();
                [$inigo, $technique] = $this->scene($world);
                $keep = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $drop = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 1);

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'adversary discards');

                $world->game->activePlayerId = 2;
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03039, 'x', $drop->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::same($inigo->Id, $discard->sourceId, 'Íñigo caused it');

                $technique->handleEvent($discard);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngarded::class), '2 remaining is more than our 1');
                unset($keep);
            },

            'calculate subtracts 2 Thrust, and cancel or duel end drops the Home move' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(-2, $calc->thrust, 'minus two');

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                $cancel = new EventTechniqueCanceled();
                $cancel->theah = $world->theah;
                $cancel->techniqueId = $technique->Id;
                $technique->handleEvent($cancel);
                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'cancel cleared the move');

                $technique->handleEvent($resolve);
                $duelEnd = new EventDuelEnd();
                $duelEnd->theah = $world->theah;
                $technique->handleEvent($duelEnd);
                $technique->handleEvent($end);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'duel end cleared the move');
            },
        ];
    }
}
