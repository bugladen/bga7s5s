<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01050_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01050';
    }

    private function equipSalve(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $salve = $world->placeCard(new _01050(), Game::LOCATION_CITY_DOCKS, 1);
        $salve->AttachedToId = $host->Id;
        $host->Attachments = [$weapon->Id, $salve->Id];
        return [$host, $salve];
    }

    public function tests(): array
    {
        return [
            'available in duel when current round thrust >= 1' => function () {
                $world = new TestWorld();
                [, $salve] = $this->equipSalve($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->currentRoundThrust = 1;

                /** @var Technique_01050 $technique */
                $technique = $salve->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when thrust is 0' => function () {
                $world = new TestWorld();
                [, $salve] = $this->equipSalve($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->currentRoundThrust = 0;

                /** @var Technique_01050 $technique */
                $technique = $salve->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no thrust');
            },

            'calculate subtracts 1 Thrust and wounds adversary' => function () {
                $world = new TestWorld();
                [$host, $salve] = $this->equipSalve($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $foe;

                /** @var Technique_01050 $technique */
                $technique = $salve->getTechniques()[0];
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->thrust = 2;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->thrust, 'thrust -1');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
            },
        ];
    }
}
