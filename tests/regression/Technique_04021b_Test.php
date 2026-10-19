<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04021b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_04021b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04021b';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde is a precondition, not an engage cost. Inside a duel she must
            // be the actor. +1 Thrust is the parent technique on both challenge and duel.
            'hidden while engaged, and adds one thrust only for her' => function () {
                $world = new TestWorld();
                $aimee = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_04021b $technique */
                $technique = $aimee->getTechniques()[1];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');
                $aimee->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
                $aimee->Engaged = false;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'other actor');
                $world->theah->duelActor = $aimee;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'she is acting');

                $challenge = new EventGenerateChallengeThreat();
                $challenge->techniqueId = $technique->Id;
                $challenge->actorId = $aimee->Id;
                $challenge->theah = $world->theah;
                $technique->handleEvent($challenge);
                Assert::same(1, $challenge->adversaryThreat, 'challenge threat');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->thrust, 'duel thrust');
            },
        ];
    }
}
