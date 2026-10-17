<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03025b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03025a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03025a';
    }

    public function tests(): array
    {
        return [
            // WHY: She must be the duel actor. Calculate adds Riposte and spends the technique.
            'adds 1 Riposte when Angeline is the actor' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _03025(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_03025a $technique */
                $technique = $angeline->getTechniques()[0];
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $other;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');

                $world->theah->duelActor = $angeline;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'she is the actor');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'plus one');
                Assert::true($technique->Used, 'spent on calculate');
            },
        ];
    }
}
