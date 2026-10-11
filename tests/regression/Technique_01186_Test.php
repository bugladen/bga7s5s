<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_01186_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01186';
    }

    /**
     * Maryam + foe at Docks; duel live.
     *
     * @return array{0:_01186,1:GenericCharacter,2:Technique_01186}
     */
    private function duel(TestWorld $world): array
    {
        $maryam = $world->placeCharacter(new _01186(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $maryam;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01186 $technique */
        $technique = $maryam->getTechniques()[0];
        return [$maryam, $foe, $technique];
    }

    /**
     * Challenge step: no duel row; adversary from CHOSEN_TARGET / event adversaryId.
     *
     * @return array{0:_01186,1:GenericCharacter,2:Technique_01186}
     */
    private function challenge(TestWorld $world): array
    {
        [$maryam, $foe, $technique] = $this->duel($world);
        $world->game->globals->set(Game::IN_DUEL, false);
        $world->theah->duelActor = null;
        $world->theah->duelOpponent = null;
        $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
        return [$maryam, $foe, $technique];
    }

    private function resolve(TestWorld $world, Technique_01186 $technique, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function newRound(TestWorld $world, Technique_01186 $technique, int $actorId, int $round = 1): void
    {
        $event = new EventDuelNewRound();
        $event->actorId = $actorId;
        $event->round = $round;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function maneuverThrows(TestWorld $world, Technique_01186 $technique, int $ownerId): bool
    {
        $event = new EventResolveManeuver();
        $event->adversaryId = $ownerId;
        $event->maneuverId = 'someManeuver';
        $event->theah = $world->theah;
        try {
            $technique->eventCheck($event);
        } catch (UserException | \BgaUserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-09-18-05): IN_DUEL was removed so Challenge Activate can offer it.
            'available outside a duel (Challenge Activate)' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->challenge($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');
            },

            'Resolve arms CancelOpponentManeuvers with AwaitingAdversaryRound' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                Assert::true($technique->CancelOpponentManeuvers, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary');
                Assert::true($technique->AwaitingAdversaryRound, 'awaiting');
                Assert::true($maryam->IsUpdated, 'dirty for DB');
            },

            // WHY: Challenge Resolve has no duel opponent — fall back to CHOSEN_TARGET.
            'Resolve with adversaryId 0 reads CHOSEN_TARGET' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);

                Assert::same($foe->Id, $technique->AdversaryId, 'from CHOSEN_TARGET');
                Assert::true($technique->AwaitingAdversaryRound, 'awaiting');
            },

            // WHY (journal 2026-09-18-05): challenger's round-1 NewRound must NOT clear the block.
            'owner NewRound while Awaiting does not clear the arm' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, $foe->Id);

                $this->newRound($world, $technique, $maryam->Id, 1);

                Assert::true($technique->CancelOpponentManeuvers, 'still armed');
                Assert::true($technique->AwaitingAdversaryRound, 'still awaiting');
            },

            'adversary NewRound clears Awaiting so the block is active' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $this->newRound($world, $technique, $foe->Id, 1);

                Assert::true($technique->CancelOpponentManeuvers, 'armed');
                Assert::false($technique->AwaitingAdversaryRound, 'active');
                Assert::true($this->maneuverThrows($world, $technique, $maryam->Id), 'blocked');
            },

            'eventCheck does not throw while still AwaitingAdversaryRound' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                Assert::false($this->maneuverThrows($world, $technique, $maryam->Id), 'not yet');
            },

            'eventCheck ignores maneuvers when Maryam is not the adversary' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->newRound($world, $technique, $foe->Id, 1);

                Assert::false($this->maneuverThrows($world, $technique, $foe->Id), 'foe not owner');
            },

            // WHY: owner's NewRound after the blocked adversary round ends the effect.
            'owner NewRound after active block clears deferred state' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);
                $this->newRound($world, $technique, $foe->Id, 1);

                $this->newRound($world, $technique, $maryam->Id, 2);

                Assert::false($technique->CancelOpponentManeuvers, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
                Assert::false($technique->AwaitingAdversaryRound, 'awaiting cleared');
            },

            // WHY (journal 2026-09-18-05): Refuse after Resolve must not leak into a later duel.
            'challenge rejected by Maryam as challenger clears the arm' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);

                $event = new EventChallengeRejected();
                $event->challengerId = $maryam->Id;
                $event->targetId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->CancelOpponentManeuvers, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            'someone else\'s rejected challenge does not clear the arm' => function () {
                $world = new TestWorld();
                [$maryam, $foe, $technique] = $this->challenge($world);
                $this->resolve($world, $technique, 0);

                $event = new EventChallengeRejected();
                $event->challengerId = $foe->Id;
                $event->targetId = $maryam->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->CancelOpponentManeuvers, 'still armed');
            },

            // WHY (journal 2026-04-08-10): canceled technique must clear deferred state.
            'EventTechniqueCanceled clears deferred state' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);

                Assert::false($technique->CancelOpponentManeuvers, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary cleared');
            },

            'duel end clears the arm so it cannot leak' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->resolve($world, $technique, $foe->Id);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);

                Assert::false($technique->CancelOpponentManeuvers, 'cleared');
            },
        ];
    }
}
