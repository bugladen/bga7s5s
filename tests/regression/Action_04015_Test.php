<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04015;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd07;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04015;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04015_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04015';
    }

    public function tests(): array
    {
        return [
            'does not ask for a performer up front' => function () {
                $action = new Action_04015();
                Assert::false($action->RequiresPerformerSelected, 'no performer');
            },

            // WHY: Dietrichs are matched by printed name, Kaspar then Daniella. The scheme
            // must be at Home, a Dietrich must be in play, and a city location must be
            // uncontrolled. Move does not engage. A discard is optional.
            'moves both Dietrichs, heals them, and may discard a city card' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04015(), Game::LOCATION_PLAYER_HOME, 1);
                $kaspar = $world->placeCharacter(new GenericCharacter('Kaspar Dietrich'), Game::LOCATION_CITY_DOCKS, 1);
                $daniella = $world->placeCharacter(new GenericCharacter('Daniella Dietrich'), Game::LOCATION_PLAYER_HOME, 1);
                $kaspar->Wounds = 1;
                $daniella->Wounds = 1;
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_OLES_INN, 2);
                /** @var Action_04015 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'garden is still uncontrolled');

                $world->theah->setLocationController(Game::LOCATION_CITY_GOVERNORS_GARDEN, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'every location is controlled');
                $world->theah->setLocationController(Game::LOCATION_CITY_GOVERNORS_GARDEN, 0);

                $city = $world->placeCard(new _04cd07(), Game::LOCATION_CITY_GOVERNORS_GARDEN, 0);
                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04015,
                    '',
                    [Game::LOCATION_CITY_GOVERNORS_GARDEN]
                );
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($kaspar->Id, $moves[0]->cardId, 'kaspar first');
                Assert::same($daniella->Id, $moves[1]->cardId, 'daniella second');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::count(2, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'both heal');
                Assert::same('04015_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'optional discard');

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04015_2);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'pass still resolves');
                Assert::same('pass', $world->game->gamestate->transitions[1], 'pass');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04015_2, '', $city->Id);
                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class)[0];
                Assert::same($city->Id, $discard->cardId, 'city card');
                Assert::true($discard->asEffect, 'effect');
            },
        ];
    }
}
