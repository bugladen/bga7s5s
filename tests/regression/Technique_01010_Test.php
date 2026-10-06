<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01010;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01010_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01010';
    }

    public function tests(): array
    {
        return [
            'resolve queues technique transition 01010' => function () {
                $world = new TestWorld();
                $sarafina = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01010 $technique */
                $technique = $sarafina->getTechniques()[0];

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition queued');
                // Technique transitions use createTechniqueTransitionEvent — check transition field
                Assert::same($sarafina->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY regression: audit fixed Strega check from getCharactersInPlayByPlayerId
            // (any location) to getCharactersAtLocationByPlayerId (same location only).
            'looks at 1 card without co-located Strega' => function () {
                $world = new TestWorld();
                $sarafina = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
                // Strega at a DIFFERENT location must NOT grant the second card
                $world->placeCharacter(
                    new GenericCharacter('Far Strega', ['Strega', 'Sorcerer']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $adversary;

                $cardA = new GenericCharacter('Top1');
                $cardA->setId(501);
                $cardB = new GenericCharacter('Top2');
                $cardB->setId(502);
                $world->game->registerDbCard($cardA);
                $world->game->registerDbCard($cardB);
                $world->game->topFactionCards = [['id' => 501], ['id' => 502]];

                /** @var Technique_01010 $technique */
                $technique = $sarafina->getTechniques()[0];
                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01010,
                    'duelChooseTechnique_01010'
                );

                Assert::count(1, $args['cards'], 'far Strega does not grant 2 cards');
            },

            'looks at 2 cards with Strega at Sarafina location' => function () {
                $world = new TestWorld();
                $sarafina = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Ally Strega', ['Strega', 'Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $adversary;

                $cardA = new GenericCharacter('Top1');
                $cardA->setId(601);
                $cardB = new GenericCharacter('Top2');
                $cardB->setId(602);
                $world->game->registerDbCard($cardA);
                $world->game->registerDbCard($cardB);
                $world->game->topFactionCards = [['id' => 601], ['id' => 602]];

                /** @var Technique_01010 $technique */
                $technique = $sarafina->getTechniques()[0];
                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01010,
                    'duelChooseTechnique_01010'
                );

                Assert::count(2, $args['cards'], 'co-located Strega grants 2 cards');
            },

            'source uses location-scoped Strega check not in-play-wide' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/techniques/Technique_01010.php');
                Assert::contains('getCharactersAtLocationByPlayerId', $src, 'location-scoped helper');
                Assert::notContains('getCharactersInPlayByPlayerId', $src, 'must not use in-play-wide helper');
                // Both getArgs and actFromTechnique paths
                Assert::same(2, substr_count($src, 'getCharactersAtLocationByPlayerId'), 'both code paths');
            },
        ];
    }
}
