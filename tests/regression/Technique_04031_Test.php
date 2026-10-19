<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Technique_04031_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04031';
    }

    public function tests(): array
    {
        return [
            // WHY: +1 Riposte always. Lethal is only while Andare is engaged, and it is
            // queued as a threat-modified event rather than a stat on the calculate event.
            // He must be the duel actor.
            'adds riposte, and lethal only while engaged' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $andare = $world->placeCharacter(new _04031(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Technique_04031 $technique */
                $technique = $andare->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no actor');
                $world->theah->duelActor = $foe;
                $world->theah->duelOpponent = $andare;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'foe is acting');
                $world->theah->duelActor = $andare;
                $world->theah->duelOpponent = $foe;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'he is acting');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->actorId = $andare->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'riposte');
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'en garde is not lethal');

                $andare->Engaged = true;
                $technique->handleEvent($calc);
                $lethal = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(true, $lethal->defenderThreatIsLethal, 'adversary threat is lethal');
                Assert::same(null, $lethal->challengerThreatIsLethal, 'his own threat is unchanged');
            },
        ];
    }
}
