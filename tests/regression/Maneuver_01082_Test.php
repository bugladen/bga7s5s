<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01082;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01082;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Maneuver_01082_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01082';
    }

    /**
     * The harness getDuelOpponentId() always returns duelOpponent, and getDuelChallengerId()/getDuelDefenderId()
     * are duelActor/duelOpponent. For arming (adversary -> our participant) our participant therefore has to be the
     * duel opponent; see swapSides() to also exercise "our participant is the challenger".
     *
     * @return array{0:_01082,1:Maneuver_01082,2:Character,3:Character} risk, maneuver, our participant, adversary
     */
    private function duel(TestWorld $world, bool $inDuel = true): array
    {
        $risk = $world->placeCard(new _01082(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $adversary;
        $world->theah->duelOpponent = $mine;
        $world->game->globals->set(Game::IN_DUEL, $inDuel);

        /** @var Maneuver_01082 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $mine, $adversary];
    }

    /** Make our participant the challenger (duelActor) and the adversary the defender (duelOpponent). */
    private function swapSides(TestWorld $world, Character $mine, Character $adversary): void
    {
        $world->theah->duelActor = $mine;
        $world->theah->duelOpponent = $adversary;
    }

    private function resolve(TestWorld $world, Maneuver_01082 $maneuver, Character $adversary): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = $adversary->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    private function destroyed(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'available in a duel against a live adversary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'resolve arms nothing visible by itself (no events queued)' => function () {
                $world = new TestWorld();
                [, $maneuver, , $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);
                Assert::count(0, $world->theah->queuedEvents, 'arming only records the participant');
            },

            'resolve for a different maneuver id does not arm' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->adversaryId = $adversary->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $maneuver->handleEvent($this->destroyed($world, $mine->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not armed');
            },

            // WHY: Final Strike - "activates if your participant is destroyed the round this card is played".
            // Our participant is the defender here, so the other side (challenger) receives +2 and Lethal.
            // NOTE: this pins the current side assignment (the non-participant side gets +2 / lethal). EventHub renders
            // challengerThreat as the challenger's OWN threat, so this may be inverted relative to card intent - flagged,
            // not endorsed.
            'participant destroyed (as defender) queues +2 lethal threat on the other side' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);

                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'threat modified');
                Assert::same(2, $threat[0]->challengerThreat, 'challenger side +2');
                Assert::same(0, $threat[0]->defenderThreat, 'participant side +0');
                Assert::same(true, $threat[0]->challengerThreatIsLethal, 'challenger side lethal');
                Assert::same(null, $threat[0]->defenderThreatIsLethal, 'participant side unchanged');
                Assert::count(1, $world->game->notify->messages, 'announced');
            },

            'participant destroyed (as challenger) queues +2 lethal threat on the defender side' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);
                $this->swapSides($world, $mine, $adversary);

                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                $threat = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threat, 'threat modified');
                Assert::same(0, $threat[0]->challengerThreat, 'participant side +0');
                Assert::same(2, $threat[0]->defenderThreat, 'defender side +2');
                Assert::same(null, $threat[0]->challengerThreatIsLethal, 'participant side unchanged');
                Assert::same(true, $threat[0]->defenderThreatIsLethal, 'defender side lethal');
            },

            'destruction of anyone else (e.g. the adversary) does not trigger' => function () {
                $world = new TestWorld();
                [, $maneuver, , $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);

                $maneuver->handleEvent($this->destroyed($world, $adversary->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not our participant');
            },

            'destruction before the maneuver is resolved does not trigger' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine] = $this->duel($world);

                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not armed (participant id 0)');
            },

            'participant destroyed outside a duel does not trigger' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);
                $world->game->globals->set(Game::IN_DUEL, false);

                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not in duel');
            },

            'ManeuverCanceled disarms Final Strike' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'canceled');
            },

            'ManeuverCanceled for a different maneuver id keeps Final Strike armed' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = 'someOtherManeuver';
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);
                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                Assert::count(1, $world->theah->queuedOfType(EventThreatModified::class), 'still armed');
            },

            // WHY: "the round this card is played" - a new round ends the window, so a later death must not fire it.
            'EventDuelNewRound disarms Final Strike' => function () {
                $world = new TestWorld();
                [, $maneuver, $mine, $adversary] = $this->duel($world);
                $this->resolve($world, $maneuver, $adversary);

                $round = new EventDuelNewRound();
                $round->theah = $world->theah;
                $maneuver->handleEvent($round);
                $maneuver->handleEvent($this->destroyed($world, $mine->Id));

                Assert::count(0, $world->theah->queuedEvents, 'new round cleared it');
            },
        ];
    }
}
