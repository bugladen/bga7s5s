<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04012;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04012_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04012';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage is printed. The challenge is Finesse and uses RAVEN_CHALLENGE_TYPE
            // so other characters cannot intervene. Resolve waits for the challenge flow.
            'issues a finesse challenge that cannot be intervened' => function () {
                $world = new TestWorld();
                $raven = $world->placeCharacter(new _04012(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04012 $action */
                $action = $raven->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');
                $raven->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $raven->Engaged = false;
                $raven->DashedFinesse = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed finesse');
                $raven->DashedFinesse = false;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04012, '', $foe->Id);
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'finesse');
                Assert::same(Game::RAVEN_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'no intervene');
                Assert::same('04012_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::same('targetChosen', $world->game->gamestate->transitions[0], 'next');
            },
        ];
    }
}
