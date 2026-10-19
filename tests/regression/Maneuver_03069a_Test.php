<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03069;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03069a_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03069a';
    }

    public function tests(): array
    {
        return [
            // WHY: Another character you control at the duel location. Harpoon throws on
            // activate so the restriction is visible, and again on confirm.
            'swaps the participant with another character you control' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03069(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $maneuver = $risk->getManeuvers()[0];
                $world->theah->duelActor = $actor;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_ID, 4);
                $world->game->globals->set(Game::DUEL_ROUND, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'other character');

                $other->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'alone');
                $other->Location = Game::LOCATION_CITY_DOCKS;

                $actor->addCondition(Game::HARPOON_CONDITION);
                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->maneuverId = $maneuver->Id;
                $threw = false;
                try {
                    $maneuver->eventCheck($activated);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'harpooned');
                $actor->removeCondition(Game::HARPOON_CONDITION);
                $maneuver->eventCheck($activated);

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::same('03069', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');

                $threw = false;
                try {
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03069, 'x', $actor->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'self');

                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03069, 'x', $other->Id);
                Assert::same($other->Id, $world->theah->swappedParticipants[0]['newId'], 'swapped');
                Assert::same([null], array_slice($world->game->gamestate->transitions, -1), 'next');
            },
        ];
    }
}
