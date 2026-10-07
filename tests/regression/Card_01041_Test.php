<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_01041_Test extends TestCase
{
    public function name(): string
    {
        return '_01041 Rosine Friese';
    }

    public function tests(): array
    {
        return [
            'constructs Character with Action_01041' => function () {
                $rosine = new _01041();
                Assert::instanceOf(IHasActions::class, $rosine, 'actions');
                Assert::same(4, $rosine->Resolve, 'Resolve');
                Assert::same(2, $rosine->Combat, 'Combat');
                Assert::same(2, $rosine->Finesse, 'Finesse');
                Assert::same(2, $rosine->Influence, 'Influence');
                Assert::true($rosine->hasTrait('Academic'), 'Academic');
                Assert::true($rosine->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Action_01041::class, $rosine->getActions()[0], 'Action_01041');
            },

            // WHY: LOCATION_PLAYER_HOME is shared — getOpposingSorcererCount short-circuits to 0
            'getOpposingSorcererCount is 0 at Player Home' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Enemy Sorcerer', ['Sorcerer']),
                    Game::LOCATION_PLAYER_HOME,
                    2
                );

                Assert::same(0, $rosine->getOpposingSorcererCount($world->theah, Game::LOCATION_PLAYER_HOME), 'home');
            },

            'moving into city with opposing Sorcerer queues +1 Influence' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Enemy Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                $event = new EventCardMoved();
                $event->cardId = $rosine->Id;
                $event->fromLocation = Game::LOCATION_PLAYER_HOME;
                $event->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($rosine, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence');
                Assert::same($rosine->ModifiedInfluence + 1, $mods[0]->NewInfluence, '+1');
            },

            'blanked removes aura when opposing Sorcerer present' => function () {
                $world = new TestWorld();
                $rosine = $world->placeCharacter(new _01041(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Enemy Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $rosine->onAbilitiesBlanked($world->theah);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'blank -1');
            },
        ];
    }
}
