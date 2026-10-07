<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01047_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01047';
    }

    private function equipPanzerWithMelee(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $panzer = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, 1);
        $sword = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $panzer->AttachedToId = $host->Id;
        $host->Attachments = [$panzer->Id, $sword->Id];
        return [$host, $panzer, $sword];
    }

    public function tests(): array
    {
        return [
            'available in duel when Melee Weapon equipped' => function () {
                $world = new TestWorld();
                [, $panzer] = $this->equipPanzerWithMelee($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Technique_01047 $technique */
                $technique = $panzer->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Melee Weapon' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $panzer = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, 1);
                $panzer->AttachedToId = $host->Id;
                $host->Attachments = [$panzer->Id];
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Technique_01047 $technique */
                $technique = $panzer->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no melee');
            },

            'calculate adds +1 Riposte when Melee Weapon present' => function () {
                $world = new TestWorld();
                [, $panzer] = $this->equipPanzerWithMelee($world);
                /** @var Technique_01047 $technique */
                $technique = $panzer->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->riposte, 'riposte');
            },
        ];
    }
}
