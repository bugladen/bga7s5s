<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04043_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04043';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde at High Drama end, and the city location must be uncontrolled
            // on the Control_ global and still claimable. Claim spends. A location that
            // became controlled before the click throws so the window stays open.
            'claims her uncontrolled location at the end of high drama' => function () {
                $world = new TestWorld();
                $sango = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_04043 $reaction */
                $reaction = $sango->getReactions()[0];
                $end = new EventHighDramaPhaseEnd();

                $world->fireOn($sango, $end);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'claim');
                Assert::true($reaction->Used, 'spent');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'docks');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $world->fireOn($sango, $end);
                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, '', 'claim');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'already claimed');
                Assert::false($reaction->Used, 'throw does not spend');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->takeQueuedEvents();
                $sango->Engaged = true;
                $world->fireOn($sango, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');

                $sango->Engaged = false;
                $sango->Location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($sango, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home');

                $sango->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $world->fireOn($sango, $end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'cannot be claimed');
            },
        ];
    }
}
