<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04004_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04004';
    }

    public function tests(): array
    {
        return [
            // WHY: Opposing means an enemy who dies at the same location as your Duelist.
            // Destroy still reports the city location. Draw spends it; pass does not.
            'draws when an enemy dies beside your duelist' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04004(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_04004 $reaction */
                $reaction = $scheme->getReactions()[0];

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $foe->Id;
                $destroyed->theah = $world->theah;
                $reaction->handleEvent($destroyed);
                Assert::same($reaction->Id, $world->theah->queuedOfType(EventTransition::class)[0]->internalId, 'offered');

                $reaction->performReaction($world->game, 0, '', 'draw');
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'draw');
                Assert::true($reaction->Used, 'used');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $destroyed->characterId = $ally->Id;
                $reaction->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'own character');

                $foe->Location = Game::LOCATION_CITY_FORUM;
                $destroyed->characterId = $foe->Id;
                $reaction->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'duelist is elsewhere');
            },
        ];
    }
}
