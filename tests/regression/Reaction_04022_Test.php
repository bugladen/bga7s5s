<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04022_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04022';
    }

    public function tests(): array
    {
        return [
            // WHY: Opposing adversary announces a combat card during a city duel at her
            // location. En garde adds a threat to both; engaged adds only yours. Pass
            // does not spend it. Home is not a city location for this reaction.
            'adds a threat to your participant, and to the adversary while she is en garde' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $axelle = $world->placeCharacter(new _04022(), Game::LOCATION_CITY_DOCKS, 1);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $mine;
                $world->theah->duelOpponent = $foe;
                /** @var Reaction_04022 $reaction */
                $reaction = $axelle->getReactions()[0];

                $announced = new EventCombatCardAnnounced();
                $announced->playerId = 2;
                $announced->theah = $world->theah;
                $reaction->handleEvent($announced);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'addThreat');
                Assert::true($reaction->Used, 'spent');
                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(1, $threat->challengerThreat, 'participant');
                Assert::same(1, $threat->defenderThreat, 'adversary while en garde');

                $reaction->Used = false;
                $axelle->Engaged = true;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($announced);
                $reaction->performReaction($world->game, 0, '', 'addThreat');
                $engaged = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(1, $engaged->challengerThreat, 'still yours');
                Assert::same(0, $engaged->defenderThreat, 'engaged skips the adversary');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'no threat');

                $announced->playerId = 1;
                $reaction->handleEvent($announced);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'your own card');

                $axelle->Location = Game::LOCATION_PLAYER_HOME;
                $announced->playerId = 2;
                $reaction->handleEvent($announced);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home');
            },
        ];
    }
}
