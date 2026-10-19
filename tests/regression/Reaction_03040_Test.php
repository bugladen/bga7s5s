<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03040_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03040';
    }

    public function tests(): array
    {
        return [
            // WHY: "A character", including an ally. Her own move does not count.
            // Uncontrolled arrivals do not. Pass does not spend. The move does not engage.
            'moves to another city location after someone arrives' => function () {
                $world = new TestWorld();
                $soline = $world->placeCharacter(new _03040(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03040 $reaction */
                $reaction = $soline->getReactions()[0];

                $moved = new EventCardMoved();
                $moved->theah = $world->theah;
                $moved->cardId = $ally->Id;
                $moved->toLocation = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($moved);
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('moveTo-' . Game::LOCATION_CITY_FORUM, $buttons, true), 'forum');
                Assert::false(in_array('moveTo-' . Game::LOCATION_CITY_DOCKS, $buttons, true), 'not her current location');
                Assert::true(in_array('pass', $buttons, true), 'pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'stays');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . Game::LOCATION_CITY_FORUM);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::true($reaction->Used, 'spent');
            },

            'her own move, an uncontrolled arrival, and Home do not offer' => function () {
                $world = new TestWorld();
                $soline = $world->placeCharacter(new _03040(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03040 $reaction */
                $reaction = $soline->getReactions()[0];
                $moved = new EventCardMoved();
                $moved->theah = $world->theah;
                $moved->cardId = $soline->Id;
                $moved->toLocation = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($moved);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'herself');

                $wild = $world->placeCharacter(new GenericCharacter('Wild'), Game::LOCATION_CITY_DOCKS, 0);
                $moved->cardId = $wild->Id;
                $reaction->handleEvent($moved);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'uncontrolled');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $soline->Location = Game::LOCATION_PLAYER_HOME;
                $moved->cardId = $ally->Id;
                $moved->toLocation = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($moved);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in the city');
            },
        ];
    }
}
