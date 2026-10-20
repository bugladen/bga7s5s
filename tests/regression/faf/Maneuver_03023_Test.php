<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
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
        return 'Maneuver_03023';
    }

    /**
     * @return array{0:Maneuver_03023,1:GenericCharacter,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03023(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Maneuver_03023 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$maneuver, $actor, $adversary];
    }

    private function wound(TestWorld $world, int $characterId, int $sourceId, int $wounds = 3): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->theah = $world->theah;
        $event->characterId = $characterId;
        $event->sourceId = $sourceId;
        $event->wounds = $wounds;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling. While active, end-of-round threat conversion (actor wounded
            // by the adversary, adversary still here) is zeroed in eventCheck and added
            // to the pending total. An absent adversary lets the wounds through.
            'carries the actor threat forward when the adversary is still here' => function () {
                $world = new TestWorld();
                [$maneuver, $actor, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::true($maneuver->IsActive, 'armed');

                $event = $this->wound($world, $actor->Id, $adversary->Id);
                $maneuver->eventCheck($event);
                Assert::same(0, $event->wounds, 'not converted');
                Assert::same(3, $world->game->globals->get(Game::PENDING_CHALLENGER_THREAT), 'actor is the challenger in the stub');

                $world->theah->duelChallengerIdOverride = 999;
                $event = $this->wound($world, $actor->Id, $adversary->Id, 2);
                $maneuver->eventCheck($event);
                Assert::same(2, $world->game->globals->get(Game::PENDING_DEFENDER_THREAT), 'defender side');
                Assert::same(0, $event->wounds, 'still suppressed');
            },

            'an absent adversary, a different source, or the end of the round lets wounds through' => function () {
                $world = new TestWorld();
                [$maneuver, $actor, $adversary] = $this->scene($world);
                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);

                $adversary->Location = Game::LOCATION_CITY_FORUM;
                $event = $this->wound($world, $actor->Id, $adversary->Id);
                $maneuver->eventCheck($event);
                Assert::same(3, $event->wounds, 'adversary left');

                $adversary->Location = $actor->Location;
                $event = $this->wound($world, $actor->Id, $actor->Id);
                $maneuver->eventCheck($event);
                Assert::same(3, $event->wounds, 'source is not the adversary');

                $end = new EventDuelEndOfRound();
                $end->theah = $world->theah;
                $maneuver->handleEvent($end);
                Assert::false($maneuver->IsActive, 'round ended');
                $event = $this->wound($world, $actor->Id, $adversary->Id);
                $maneuver->eventCheck($event);
                Assert::same(3, $event->wounds, 'no longer active');

                $maneuver->IsActive = true;
                $canceled = new EventManeuverCanceled();
                $canceled->theah = $world->theah;
                $canceled->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($canceled);
                Assert::false($maneuver->IsActive, 'canceled');
            },
        ];
    }
}
