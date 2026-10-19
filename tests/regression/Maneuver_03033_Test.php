<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_03033_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03033';
    }

    /**
     * @return array{0:Maneuver_03033,1:GenericCharacter,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03033(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Adversary'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->ModifiedInfluence = 2;
        $adversary->ModifiedInfluence = 2;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Maneuver_03033 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$maneuver, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling, and Influence greater or equal. Equal qualifies. The wound
            // is on resolve and does not recheck Influence.
            'wounds the adversary when Influence is at least equal' => function () {
                $world = new TestWorld();
                [$maneuver, $actor, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'equal influence');

                $actor->ModifiedInfluence = 1;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'less influence');
                $actor->ModifiedInfluence = 3;

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $world->game->discardOrLockerByCardId[$adversary->Id] = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'adversary gone');
                $world->game->discardOrLockerByCardId[$adversary->Id] = false;

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::same($adversary->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'adversary wounded');
            },
        ];
    }
}
