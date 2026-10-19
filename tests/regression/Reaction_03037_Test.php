<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03037_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03037';
    }

    public function tests(): array
    {
        return [
            // WHY: Only Sanjay's own refused challenge, and only when the location still
            // has Renown. Pass does not spend. Collect removes one and gains one.
            'collects a Renown after Sanjay\'s challenge is refused' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_03037 $reaction */
                $reaction = $sanjay->getReactions()[0];

                $rejected = new EventChallengeRejected();
                $rejected->theah = $world->theah;
                $rejected->challengerId = $sanjay->Id;
                $reaction->handleEvent($rejected);
                Assert::same(['collect', 'pass'], array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'), 'collect or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'no collect');

                $world->game->gamestate->transitions = [];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'collect');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0]->location, 'from the docks');
                Assert::same(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class)[0]->amount, 'one renown');
                Assert::true($reaction->Used, 'spent');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'another challenger, or a location with no Renown, does not offer' => function () {
                $world = new TestWorld();
                $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03037 $reaction */
                $reaction = $sanjay->getReactions()[0];
                $rejected = new EventChallengeRejected();
                $rejected->theah = $world->theah;
                $rejected->challengerId = $sanjay->Id;
                $reaction->handleEvent($rejected);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no renown');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $rejected->challengerId = 999;
                $reaction->handleEvent($rejected);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not his challenge');

                $sanjay->Location = Game::LOCATION_PLAYER_HOME;
                $rejected->challengerId = $sanjay->Id;
                $threw = false;
                try {
                    $reaction->handleEvent($rejected);
                } catch (\Exception $e) {
                    $threw = str_contains($e->getMessage(), 'Player Home');
                }
                Assert::true($threw, 'Home is not a city location, so the lookup throws');
            },
        ];
    }
}
