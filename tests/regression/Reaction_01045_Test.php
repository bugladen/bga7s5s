<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01045_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01045';
    }

    public function tests(): array
    {
        return [
            'offers at HD end when scheme at home and no available mercs/attachments' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);

                /** @var Reaction_01045 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventHighDramaPhaseEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'does not offer when available Mercenary in city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    0
                );
                // ControllerId 0 = uncontrolled available merc

                /** @var Reaction_01045 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventHighDramaPhaseEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'merc blocks');
            },

            'does not offer when available attachment in city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 0);

                /** @var Reaction_01045 $reaction */
                $reaction = $scheme->getReactions()[0];
                $event = new EventHighDramaPhaseEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'att blocks');
            },

            'gainReknown awards renown and marks used' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_01045 $reaction */
                $reaction = $scheme->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gainReknown');

                Assert::count(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'renown');
                Assert::true($reaction->Used, 'used');
            },
        ];
    }
}
