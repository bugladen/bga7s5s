<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_01011_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01011';
    }

    public function tests(): array
    {
        return [
            'duel calc adds +1 Thrust per OTHER controlled Red Hand here' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Second RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Far RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                /** @var Technique_01011 $technique */
                $technique = $servo->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $servo->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->thrust, 'two co-located other Red Hands');
                Assert::count(1, $event->explanations, 'explanation');
            },

            // WHY regression: audit 2026-04-13 — must filter ControllerId (your Red Hands only)
            'opponent Red Hand at location does not add Thrust' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Enemy RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                /** @var Technique_01011 $technique */
                $technique = $servo->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $servo->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->thrust, 'enemy Red Hand ignored');
            },

            // WHY: Dead challenger is in Locker; Red Hands remain at challenge city site
            'challenge threat uses CHOSEN_LOCATION when actor is in locker' => function () {
                $world = new TestWorld();
                $servo = $world->placeCharacter(new _01011(), Game::LOCATION_CITY_LOCKER, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally RH', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->game->forceInDiscardOrLocker = true;
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                /** @var Technique_01011 $technique */
                $technique = $servo->getTechniques()[0];

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $servo->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, 'locker uses CHOSEN_LOCATION Red Hands');
            },

            'source filters own controller on both event paths' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/techniques/Technique_01011.php');
                Assert::same(2, substr_count($src, 'ControllerId == $actor->ControllerId'), 'both paths own Red Hands');
            },
        ];
    }
}
