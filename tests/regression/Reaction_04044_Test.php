<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04044_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04044';
    }

    public function tests(): array
    {
        return [
            // WHY: Leader trait on the challenger, and CityLocation->Controller is already
            // non-zero. Uncontrol spends. Pass does not. A non-leader or an uncontrolled
            // site is not an offer.
            'makes the leader performer location uncontrolled' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04044(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_04044 $reaction */
                $reaction = $scheme->getReactions()[0];

                $issued = new EventChallengeIssued();
                $issued->challengerId = $leader->Id;
                $world->fireOn($scheme, $issued);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class), 'no uncontrol');

                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $issued);
                $reaction->performReaction($world->game, 0, '', 'uncontrol');
                Assert::true($reaction->Used, 'spent');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0]->location, 'docks');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $issued->challengerId = $hero->Id;
                $world->fireOn($scheme, $issued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a leader');

                $issued->challengerId = $leader->Id;
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $world->fireOn($scheme, $issued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'already uncontrolled');

                $issued->canceled = true;
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($scheme, $issued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'canceled challenge');
            },
        ];
    }
}
