<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04031_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04031';
    }

    public function tests(): array
    {
        return [
            // WHY: Round 1 only, Andare en garde, a duel at his location, and your
            // participant already has a threat. Remove spends the reaction. Pass does not.
            // Zero threat is not an offer.
            'removes one threat from your participant on round one' => function () {
                $world = new TestWorld();
                $andare = $world->placeCharacter(new _04031(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $andare;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::CHALLENGER_THREAT, 2);
                /** @var Reaction_04031 $reaction */
                $reaction = $andare->getReactions()[0];

                $round = new EventDuelNewRound();
                $round->round = 1;
                $round->challengerId = $andare->Id;
                $round->defenderId = $foe->Id;
                $round->theah = $world->theah;
                $reaction->handleEvent($round);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'removeThreat');
                Assert::true($reaction->Used, 'spent');
                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(-1, $threat->challengerThreat, 'your participant');
                Assert::same(0, $threat->defenderThreat, 'not the adversary');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');

                $round->round = 2;
                $reaction->handleEvent($round);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'later round');

                $round->round = 1;
                $world->game->globals->set(Game::CHALLENGER_THREAT, 0);
                $reaction->handleEvent($round);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no threat to remove');

                $world->game->globals->set(Game::CHALLENGER_THREAT, 1);
                $andare->Engaged = true;
                $reaction->handleEvent($round);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');
            },
        ];
    }
}
