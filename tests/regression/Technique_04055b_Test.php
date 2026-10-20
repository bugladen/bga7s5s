<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04055b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_04055b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04055b';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling technique. The actor must have gambled this round, and must
            // be the bearer. Engage state does not hide it, and resolve does not engage.
            'adds one parry only after a gamble' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $shield = $world->placeCard(new _04055(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $shield->AttachedToId = $bearer->Id;
                $shield->Engaged = true;
                $world->theah->duelActor = $bearer;
                /** @var Technique_04055b $technique */
                $technique = $shield->getTechniques()[1];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'has not gambled');

                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'engaged shield still gambles');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->parry = 0;
                $world->fireOn($shield, $calc);
                Assert::same(1, $calc->parry, '+1 parry');

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $world->fireOn($shield, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage cost');

                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'gamble flag cleared');
            },
        ];
    }
}
