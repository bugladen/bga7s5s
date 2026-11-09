<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Refuge;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Refuge;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Refuge_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Refuge';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic Action (not City) — RiskAction admits home performers. Destinations
            // are Home and claim-controlled Cities via Control_ globals (not CityLocation->Controller).
            'is a plain RiskAction that moves home or to a controlled city' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Refuge(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Thug'), Game::LOCATION_CITY_DOCKS, 1);
                $homeAcademic = $world->placeCharacter(new GenericCharacter('Home Scholar', ['Academic']), Game::LOCATION_PLAYER_HOME, 1);
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_05Refuge $action */
                $action = $risk->getActions()[0];
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'academic with a dest');

                $performers = $action->getPerformersForAction(1, $world->theah);
                $performerIds = array_map(fn($c) => $c->Id, $performers);
                Assert::true(in_array($academic->Id, $performerIds, true), 'city academic');
                Assert::true(in_array($homeAcademic->Id, $performerIds, true), 'home academic');
                Assert::false(in_array($plain->Id, $performerIds, true), 'non-Academic greyed');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);
                $destinations = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REFUGE, '')['locationIds'];
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $destinations, true), 'Home');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $destinations, true), 'controlled Forum');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $destinations, true), 'not current');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REFUGE, '', [Game::LOCATION_PLAYER_HOME]);
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($academic->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to Home');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_05REFUGE, '', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'current location rejected');
            },

            'artifact equipment discounts the Action pay; combat-card stays full cost' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Refuge(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                $artifact = $world->placeCard(new GenericAttachment('Relic'), Game::LOCATION_CITY_DOCKS, 1);
                $artifact->Traits = ['Artifact'];
                $artifact->ModifiedTraits = ['Artifact'];
                $academic->Attachments[] = $artifact->Id;
                /** @var Action_05Refuge $action */
                $action = $risk->getActions()[0];

                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $academic, $action, $explanations), 'artifact');
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'no performer');

                $plain = $world->placeCharacter(new GenericCharacter('Bare', ['Academic']), Game::LOCATION_CITY_FORUM, 1);
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $plain, $action, $explanations), 'no artifact');
                Assert::same(1, $risk->WealthCost, 'printed cost unchanged');
            },

            'queues the location chooser on ActionTriggered' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Refuge(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_05Refuge $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same('05Refuge', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'key');
            },

            'hides when no Academic has a legal destination' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Refuge(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Home Only', ['Academic']), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_05Refuge $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home with no controlled City');
            },
        ];
    }
}
