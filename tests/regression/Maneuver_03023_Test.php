<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03023_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03023 Second Wind';
    }

    /**
     * @return array{0:_03023,1:Maneuver_03023,2:Character,3:Character} risk, maneuver, actor, adversary
     */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03023(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_GAMBLED, true);

        /** @var Maneuver_03023 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $adversary];
    }

    private function activate(TestWorld $world, Maneuver_03023 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    private function leftoverThreatWound(TestWorld $world, Character $actor, Character $adversary, int $wounds): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->characterId = $actor->Id;
        $event->sourceId = $adversary->Id;
        $event->wounds = $wounds;
        $event->reason = "<p>$wounds Threat was left over in their Pool.";
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'Gambling Maneuver available only when DUEL_GAMBLED' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
            },

            // WHY: suppression must live in eventCheck so reaction clones see wounds=0
            // (Breastplate leftover-threat trap — journal 2026-08-21-01).
            'eventCheck zeros leftover-threat wounds and stashes PENDING for actor side' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary] = $this->duel($world);
                $this->activate($world, $maneuver);

                $event = $this->leftoverThreatWound($world, $actor, $adversary, 2);
                $maneuver->eventCheck($event);

                Assert::same(0, $event->wounds, 'suppressed');
                Assert::same(2, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'challenger pending');
                Assert::same(0, $world->game->globals->get(Game::PENDING_DEFENDER_THREAT, 0), 'defender untouched');
            },

            'eventCheck does nothing when maneuver is not active' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary] = $this->duel($world);

                $event = $this->leftoverThreatWound($world, $actor, $adversary, 2);
                $maneuver->eventCheck($event);

                Assert::same(2, $event->wounds, 'unchanged');
                Assert::same(0, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'no pending');
            },

            'eventCheck does nothing when adversary is absent' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary] = $this->duel($world);
                $this->activate($world, $maneuver);
                $adversary->Location = Game::LOCATION_PLAYER_HOME;

                $event = $this->leftoverThreatWound($world, $actor, $adversary, 2);
                $maneuver->eventCheck($event);

                Assert::same(2, $event->wounds, 'absent adversary — wounds convert');
                Assert::same(0, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'no pending');
            },

            'eventCheck ignores wounds that are not actor-from-adversary leftover threat' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $this->activate($world, $maneuver);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $actor->Id;
                $event->sourceId = 999; // not the adversary
                $event->wounds = 2;
                $event->theah = $world->theah;
                $maneuver->eventCheck($event);

                Assert::same(2, $event->wounds, 'other source');
            },

            // WHY: handleEvent must not also suppress — that was the production bug.
            'handleEvent on BeingWounded does not zero wounds' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $adversary] = $this->duel($world);
                $this->activate($world, $maneuver);

                $event = $this->leftoverThreatWound($world, $actor, $adversary, 2);
                $maneuver->handleEvent($event);

                Assert::same(2, $event->wounds, 'handleEvent leaves wounds');
                Assert::same(0, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT, 0), 'no pending from handleEvent');
            },

            'EventDuelEndOfRound clears IsActive' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->activate($world, $maneuver);
                Assert::true($maneuver->IsActive, 'armed');

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);

                Assert::false($maneuver->IsActive, 'cleared');
            },

            'EventManeuverCanceled clears IsActive' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $this->activate($world, $maneuver);

                $cancel = new EventManeuverCanceled();
                $cancel->maneuverId = $maneuver->Id;
                $cancel->theah = $world->theah;
                $maneuver->handleEvent($cancel);

                Assert::false($maneuver->IsActive, 'canceled');
            },
        ];
    }
}
