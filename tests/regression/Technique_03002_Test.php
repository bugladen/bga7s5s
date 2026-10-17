<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Technique_03002_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03002';
    }

    /**
     * @return array{0:_03002,1:Technique_03002,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $aja = $world->placeCharacter(new _03002(), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $adversary->Wounds = 1;
        $world->theah->duelActor = $aja;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::DUEL_GAMBLED, true);
        /** @var Technique_03002 $technique */
        $technique = $aja->getTechniques()[0];
        return [$aja, $technique, $adversary];
    }

    public function tests(): array
    {
        return [
            'is a technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_03002(), 'technique');
            },

            'available only while Aja is the actor, she gambled, and the adversary is wounded' => function () {
                $world = new TestWorld();
                [$aja, $technique, $adversary] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $adversary->Wounds = 0;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'adversary unwounded');
                $adversary->Wounds = 1;

                $world->theah->duelActor = $adversary;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'she is not the actor');
                unset($aja);
            },

            // WHY: The wound check is availability only. Calculate still grants Lethal
            // when this technique id matches, even if the adversary was healed since.
            'calculate grants Lethal to the adversary side' => function () {
                $world = new TestWorld();
                [$aja, $technique, $adversary] = $this->scene($world);
                $adversary->Wounds = 0;
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $aja->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $lethal = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $lethal, 'lethal');
                Assert::same(null, $lethal[0]->challengerThreatIsLethal, 'actor side unchanged');
                Assert::same(true, $lethal[0]->defenderThreatIsLethal, 'adversary becomes lethal');
            },

            'a different technique id does nothing' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
