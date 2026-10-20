<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_04054a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04054a';
    }

    public function tests(): array
    {
        return [
            // WHY: The printed +1 Thrust is the shared technique with this card's own id.
            // Challenge threat applies when the bearer is the actor. Another actor does not
            // get it while the sabre still has a host.
            'adds thrust, and challenge threat for the bearer' => function () {
                $world = new TestWorld();
                $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $sabre->AttachedToId = $bearer->Id;
                $technique = $sabre->getTechniques()[0];
                Assert::instanceOf(Technique_PlusOneThrust::class, $technique, 'shared thrust');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->thrust = 1;
                $world->fireOn($sabre, $calc);
                Assert::same(2, $calc->thrust, '+1 thrust');

                $threat = new EventGenerateChallengeThreat();
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $bearer->Id;
                $threat->adversaryThreat = 0;
                $world->fireOn($sabre, $threat);
                Assert::same(1, $threat->adversaryThreat, 'bearer');

                $threat->actorId = $foe->Id;
                $threat->adversaryThreat = 0;
                $world->fireOn($sabre, $threat);
                Assert::same(0, $threat->adversaryThreat, 'not the bearer');
            },
        ];
    }
}
