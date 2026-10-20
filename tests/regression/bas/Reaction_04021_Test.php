<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04021;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04021_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04021';
    }

    public function tests(): array
    {
        return [
            // WHY: Continuous reaction. An opponent's effect (source controller is another
            // player) engaging your other Musketeer here may en garde them. sourceId 0 is
            // framework auto-engage. En Garde does not set Used, so it can fire again.
            'offers to en garde the other musketeer and does not spend the reaction' => function () {
                $world = new TestWorld();
                $aimee = $world->placeCharacter(new _04021(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                $ally->Engaged = true;
                $source = $world->placeCard(new _04027(), Game::LOCATION_HAND, 2);
                /** @var Reaction_04021 $reaction */
                $reaction = $aimee->getReactions()[0];

                $engaged = new EventCardEngaged();
                $engaged->cardId = $ally->Id;
                $engaged->sourceId = $source->Id;
                $engaged->theah = $world->theah;
                $reaction->handleEvent($engaged);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
                Assert::same($ally->Id, $reaction->engagedMusketeerId, 'remembered');

                $reaction->performReaction($world->game, 0, '', 'enGarde');
                Assert::false($reaction->Used, 'continuous');
                Assert::same(0, $reaction->engagedMusketeerId, 'cleared');
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::same('done', end($world->game->gamestate->transitions), 'done');

                $world->theah->takeQueuedEvents();
                $engaged->sourceId = 0;
                $reaction->handleEvent($engaged);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'framework engage');

                $engaged->sourceId = $aimee->Id;
                $reaction->handleEvent($engaged);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'her own effect');

                $engaged->sourceId = $source->Id;
                $engaged->cardId = $aimee->Id;
                $reaction->handleEvent($engaged);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not herself');

                $aimee->ControllerId = 0;
                $engaged->cardId = $ally->Id;
                $reaction->handleEvent($engaged);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'uncontrolled');
            },
        ];
    }
}
