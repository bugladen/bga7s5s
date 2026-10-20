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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04060_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04060';
    }

    public function tests(): array
    {
        return [
            // WHY: Any controlled city location, not only yours, with no Renown or no
            // characters. Either half qualifies. A location that fails can* is hidden.
            // The click uncontrols and resolves. An illegal location throws.
            'uncontrols a controlled location with no renown or no characters' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04060(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 3);
                $world->theah->setLocationController(Game::LOCATION_CITY_BAZAAR, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 2);
                $world->placeCharacter(new GenericCharacter('There'), Game::LOCATION_CITY_BAZAAR, 2);
                /** @var Action_04060 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'docks has no renown');

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same('04060', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choose');

                $locations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04060, '')['locationIds'];
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $locations, true), 'no renown, even with a character');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $locations, true), 'no characters, even with renown');
                Assert::false(in_array(Game::LOCATION_CITY_BAZAAR, $locations, true), 'renown and a character');

                $blocked = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04060, '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'not eligible');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04060, '', [Game::LOCATION_CITY_FORUM]);
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0]->location, 'uncontrolled');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('locationChosen', end($world->game->gamestate->transitions), 'done');

                $world->theah->setLocationCanBecomeUncontrolled(Game::LOCATION_CITY_DOCKS, false);
                $world->theah->setLocationCanBecomeUncontrolled(Game::LOCATION_CITY_FORUM, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing can become uncontrolled');
            },
        ];
    }
}
