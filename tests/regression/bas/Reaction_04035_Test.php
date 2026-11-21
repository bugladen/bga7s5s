<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04035_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04035';
    }

    public function tests(): array
    {
        return [
            // WHY: Offer whenever pressure occurs at a location with your Academic — your own
            // pressure or an opponent's (Pack Tactics). Use ORs the flag; Pass does not spend.
            'offers on pressure at academic location including opponent' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04035(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Also', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_04035 $reaction */
                $reaction = $scheme->getReactions()[0];

                $pressure = new EventPressureOccuring();
                $pressure->playerId = 1;
                $pressure->location = Game::LOCATION_CITY_DOCKS;
                $pressure->theah = $world->theah;
                $reaction->handleEvent($pressure);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered on your pressure');

                $reaction->performReaction($world->game, 0, '', 'use');
                Assert::true($reaction->Used, 'spent');
                Assert::same(
                    Game::NORMAL_PRESSURE_TYPE | Game::MEETING_OF_THE_MINDS_PRESSURE_TYPE,
                    $world->game->globals->get(Game::PRESSURE_TYPE),
                    'flag'
                );
                Assert::same(1, $world->game->globals->get(Game::MEETING_OF_THE_MINDS_PLAYER_ID), 'bonus player');

                $reaction->Used = false;
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'unchanged');

                // WHY: Opponent Pack Tactics (playerId 2) at your Academic's location must still offer.
                $pressure->playerId = 2;
                $reaction->handleEvent($pressure);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered on opponent pressure');

                $world->theah->takeQueuedEvents();
                $pressure->playerId = 1;
                $pressure->location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no academic there');
            },
        ];
    }
}
