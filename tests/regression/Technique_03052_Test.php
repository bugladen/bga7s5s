<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03052_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03052';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling technique. An empty adversary hand is hidden rather than a useless look.
            // Resolve marks it used and opens a private acknowledgement. Pass just leaves the state.
            'looks at the adversary hand after a gamble' => function () {
                $world = new TestWorld();
                $yev = $world->placeCharacter(new _03052(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $hand = $world->placeCard(new _03056(), Game::LOCATION_HAND, 2);
                $technique = $yev->getTechniques()[0];
                $world->theah->duelActor = $yev;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'hand');

                $hand->Location = Game::LOCATION_CITY_DECK;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'empty hand');
                $hand->Location = Game::LOCATION_HAND;

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $yev;

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::true($technique->Used, 'used');
                Assert::same('03052', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'look');

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03052, 'x');
                Assert::same($hand->Id, $args['cards'][0]['id'], 'shown');
                $technique->actFromTechniquePass($world->game, States::DUEL_CHOOSE_TECHNIQUE_03052);
                Assert::same([null], array_slice($world->game->gamestate->transitions, -1), 'pass');
            },
        ];
    }
}
