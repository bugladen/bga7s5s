<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03071;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03071_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03071';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03071();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: The location must be controlled by an opponent, and the target must be an
            // unengaged opposing character. A Villain or Pirate Leader discounts this action by 1.
            'engages an opposing character at an opponent location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03071(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03071 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'your location');
                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'their location');
                $foe->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already engaged');
                $foe->Engaged = false;

                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'hero leader');
                $leader->ModifiedTraits = ['Leader', 'Villain'];
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'villain');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03071', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03071, 'x', $ally->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'ally');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03071, 'x', $foe->Id);
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');
            },
        ];
    }
}
