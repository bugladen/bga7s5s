<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_03018_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03018';
    }

    public function tests(): array
    {
        return [
            'is a technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_03018(), 'technique');
            },

            // WHY: Available whenever you are in a duel and the adversary is a Sorcerer.
            // It does not require the technique's owner to be the actor.
            // Calculate adds Thrust without checking the trait again.
            'adds 1 Thrust when the adversary is a Sorcerer' => function () {
                $world = new TestWorld();
                $owner = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $adversary = $world->placeCharacter(new GenericCharacter('Adversary', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                $technique = new Technique_03018();
                $technique->setId('Technique_03018');
                $technique->setOwnerId($owner->Id);
                $owner->addTechnique($technique, $world->game, false);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $adversary;
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');

                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'adversary is a sorcerer');

                $adversary->addTrait($world->game, 'Hero', true);
                $adversary->removeTrait($world->game, 'Sorcerer');
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not a sorcerer');
                $adversary->addTrait($world->game, 'Sorcerer', true);

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->thrust, 'plus one');
                Assert::same(0, $calc->riposte, 'riposte untouched');
            },
        ];
    }
}
