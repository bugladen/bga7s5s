<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03068_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03068';
    }

    public function tests(): array
    {
        return [
            'is a risk reaction' => function () {
                Assert::instanceOf(RiskReaction::class, new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03068(), 'risk reaction');
            },

            // WHY: Opponent pass, risk in hand, you have a character in the city, and they have
            // an en garde character at Home. Your own pass is not an offer. Use pays; Pass does
            // not spend. The opponent then picks the character and a city location. Move does not engage.
            'forces the opponent to move an en garde character from home' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03068(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);
                /** @var \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03068 $reaction */
                $reaction = $risk->getReactions()[0];
                $passed = new EventHighDramaPhasePlayerPassed();
                $passed->theah = $world->theah;
                $passed->playerId = 1;
                $reaction->handleEvent($passed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'your pass');

                $passed->playerId = 2;
                $home->Engaged = true;
                $reaction->handleEvent($passed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');
                Assert::same(0, $reaction->opposingPlayerId, 'reset');
                $home->Engaged = false;

                $reaction->handleEvent($passed);
                Assert::same(1, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'offered to you');
                Assert::same('', $reaction->stage, 'offer');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::same(0, $reaction->opposingPlayerId, 'pass clears');
                Assert::false($reaction->Used, 'pass does not spend');

                $reaction->opposingPlayerId = 2;
                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                Assert::false($reaction->Used, 'pay not yet spent');

                $world->theah->queuedEvents = [];
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::same('chooseCharacter', $reaction->stage, 'character');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'their turn');

                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'nope');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bad character');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'character-' . $home->Id);
                Assert::same('chooseLocation', $reaction->stage, 'location');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . Game::LOCATION_CITY_FORUM);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::true($reaction->Used, 'spent');
                Assert::same('', $reaction->stage, 'finalized');
            },
        ];
    }
}
