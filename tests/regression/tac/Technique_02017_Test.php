<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_02017_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02017';
    }

    /**
     * Panzerhand on challenger (yours); foe is adversary.
     *
     * @return array{0:_02017,1:Technique_02017,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $panzer = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 1);
        $panzer->AttachedToId = $challenger->Id;
        $challenger->Attachments[] = $panzer->Id;

        /** @var Technique_02017 $technique */
        $technique = $panzer->getTechniques()[0];
        return [$panzer, $technique, $challenger, $foe];
    }

    private function arm(Technique_02017 $technique, TestWorld $world, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    private function newRound(TestWorld $world, int $actorId): EventDuelNewRound
    {
        $event = new EventDuelNewRound();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: use event->adversaryId (or CHOSEN_TARGET) — challenge has no duel yet.
            'EventResolveTechnique arms NoTechniquesThisRound, AdversaryId, AwaitingAdversaryRound' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                Assert::true($technique->NoTechniquesThisRound, 'armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'adversary');
                Assert::true($technique->AwaitingAdversaryRound, 'awaiting');
            },

            'EventResolveTechnique falls back to CHOSEN_TARGET when adversaryId empty' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->adversaryId = 0;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same($foe->Id, $technique->AdversaryId, 'from globals');
            },

            // WHY: block only after Awaiting cleared on adversary NewRound.
            'eventCheck throws when armed, not awaiting, and activator is AdversaryId' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);
                $technique->AwaitingAdversaryRound = false;

                $activated = new EventTechniqueActivated();
                $activated->ownerId = $foe->Id;
                $activated->techniqueId = 'Technique_Other';
                $activated->theah = $world->theah;

                $threw = false;
                try {
                    $technique->eventCheck($activated);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'eventCheck allows while still AwaitingAdversaryRound' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $activated = new EventTechniqueActivated();
                $activated->ownerId = $foe->Id;
                $activated->theah = $world->theah;
                $technique->eventCheck($activated);
                Assert::true(true, 'no throw while awaiting');
            },

            'EventDuelNewRound for adversary clears Awaiting (block now active)' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $technique->handleEvent($this->newRound($world, $foe->Id));

                Assert::false($technique->AwaitingAdversaryRound, 'cleared');
                Assert::true($technique->NoTechniquesThisRound, 'still armed');
            },

            // WHY (01186 / 01193 premature-clear): challenger's round-1 NewRound must NOT clear.
            'challenger NewRound while still Awaiting does not clear deferred state' => function () {
                $world = new TestWorld();
                [, $technique, $challenger, $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $technique->handleEvent($this->newRound($world, $challenger->Id));

                Assert::true($technique->AwaitingAdversaryRound, 'still awaiting');
                Assert::true($technique->NoTechniquesThisRound, 'still armed');
                Assert::same($foe->Id, $technique->AdversaryId, 'kept');
            },

            // WHY: Resolve runs before Accept/Reject — refuse must clear or flag leaks.
            'EventChallengeRejected when owning character is challenger clears deferred state' => function () {
                $world = new TestWorld();
                [, $technique, $challenger, $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $event = new EventChallengeRejected();
                $event->challengerId = $challenger->Id;
                $event->targetId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->NoTechniquesThisRound, 'cleared');
                Assert::same(0, $technique->AdversaryId, 'adversary 0');
                Assert::false($technique->AwaitingAdversaryRound, 'awaiting false');
            },

            'EventDuelEnd clears deferred state' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->NoTechniquesThisRound, 'cleared');
            },

            'EventTechniqueCanceled clears deferred state' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($technique, $world, $foe->Id);

                $event = new EventTechniqueCanceled();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->NoTechniquesThisRound, 'cleared');
            },
        ];
    }
}
