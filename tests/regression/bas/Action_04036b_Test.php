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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04036b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;

class Action_04036b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04036b';
    }

    public function tests(): array
    {
        return [
            // WHY: The destination needs strictly more Renown. Adjacency is not required.
            // Engage is the tome. The bearer moves without engaging.
            'moves the bearer to a richer city location' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $tome = $world->placeCard(new _04036(), Game::LOCATION_CITY_DOCKS, 1);
                $tome->AttachedToId = $host->Id;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 3);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_04036b $action */
                $action = $tome->getActions()[1];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'bazaar is richer');
                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036b, '')['locationIds'];
                Assert::same([Game::LOCATION_CITY_BAZAAR], $destinations, 'equal forum is excluded');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036b, '', [Game::LOCATION_CITY_BAZAAR]);
                Assert::same($tome->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'tome engaged');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($host->Id, $move->cardId, 'bearer');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $move->toLocation, 'bazaar');
                Assert::false($move->engage, 'move does not engage the bearer');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04036b, '', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not richer');
            },
        ];
    }
}
