<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelAttemptGamble;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_02037_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02037';
    }

    /**
     * @return array{0:_02037,1:Technique_02037,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $mysta = $world->placeCard(new _02037(), Game::LOCATION_CITY_DOCKS, 1);
        $mysta->AttachedToId = $host->Id;
        /** @var Technique_02037 $technique */
        $technique = $mysta->getTechniques()[0];
        return [$mysta, $technique, $host, $foe];
    }

    private function arm(TestWorld $world, Technique_02037 $technique, int $adversaryId): void
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                $technique = new Technique_02037();
                Assert::instanceOf(Technique::class, $technique, 'Technique');
                Assert::false($technique->CancelAdversaryGamble, 'disarmed');
                Assert::same(0, $technique->BlockedAdversaryCharacterId, 'nobody');
                Assert::false($technique->AwaitingAdversaryRound, 'not waiting');
            },

            // WHY: Arm from adversaryId on Resolve. Challenge has no duel round yet.
            'resolve arms a gamble block against the adversary' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);

                $this->arm($world, $technique, $foe->Id);

                Assert::true($technique->CancelAdversaryGamble, 'armed');
                Assert::same($foe->Id, $technique->BlockedAdversaryCharacterId, 'foe');
                Assert::true($technique->AwaitingAdversaryRound, 'waiting');
            },

            'resolve falls back to CHOSEN_TARGET when adversaryId is empty' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->adversaryId = 0;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same($foe->Id, $technique->BlockedAdversaryCharacterId, 'from global');
                Assert::true($technique->AwaitingAdversaryRound, 'waiting');
            },

            'TechniqueCanceled clears the lock' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventTechniqueCanceled();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->CancelAdversaryGamble, 'cleared');
                Assert::same(0, $technique->BlockedAdversaryCharacterId, 'nobody');
                Assert::false($technique->AwaitingAdversaryRound, 'not waiting');
            },

            // WHY: Refuse never starts a duel. Clear so the flag cannot leak into a later duel.
            'challenge rejected by the owning character clears the lock' => function () {
                $world = new TestWorld();
                [, $technique, $host, $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventChallengeRejected();
                $event->challengerId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->CancelAdversaryGamble, 'cleared');
            },

            'challenge rejected by someone else leaves the lock armed' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventChallengeRejected();
                $event->challengerId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->CancelAdversaryGamble, 'still armed');
                Assert::true($technique->AwaitingAdversaryRound, 'still waiting');
            },

            // WHY: Challenger round 1 NewRound must not clear the block (01186 / 01193 bug).
            'owner NewRound while still awaiting does not clear the block' => function () {
                $world = new TestWorld();
                [, $technique, $host, $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventDuelNewRound();
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->CancelAdversaryGamble, 'still armed');
                Assert::true($technique->AwaitingAdversaryRound, 'still waiting');
            },

            'adversary NewRound makes the block active for that round' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventDuelNewRound();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->CancelAdversaryGamble, 'still armed');
                Assert::false($technique->AwaitingAdversaryRound, 'their round');
                Assert::same($foe->Id, $technique->BlockedAdversaryCharacterId, 'still them');
            },

            'owner NewRound after the blocked round clears the lock' => function () {
                $world = new TestWorld();
                [, $technique, $host, $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);
                $technique->AwaitingAdversaryRound = false;

                $event = new EventDuelNewRound();
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->CancelAdversaryGamble, 'cleared');
                Assert::same(0, $technique->BlockedAdversaryCharacterId, 'nobody');
            },

            'duel end clears the lock' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->CancelAdversaryGamble, 'cleared');
            },

            'gamble during the armed adversary round is rejected' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);
                $technique->AwaitingAdversaryRound = false;

                $event = new EventDuelAttemptGamble();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $technique->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'gamble before the adversary round, or by the owner, is allowed' => function () {
                $world = new TestWorld();
                [, $technique, $host, $foe] = $this->scene($world);
                $this->arm($world, $technique, $foe->Id);

                $early = new EventDuelAttemptGamble();
                $early->actorId = $foe->Id;
                $early->theah = $world->theah;
                $technique->eventCheck($early);

                $technique->AwaitingAdversaryRound = false;
                $owner = new EventDuelAttemptGamble();
                $owner->actorId = $host->Id;
                $owner->theah = $world->theah;
                $technique->eventCheck($owner);

                Assert::true($technique->CancelAdversaryGamble, 'still armed for the foe');
            },
        ];
    }
}
