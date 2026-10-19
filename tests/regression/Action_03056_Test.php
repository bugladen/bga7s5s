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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03056_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03056';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03056();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Renown is the effect, not a targeting cost. Their controller must not
            // already control the location. The claim is theirs. No renown resolves immediately.
            // A location that cannot be claimed resolves without the renown step.
            'the target controller claims, then renown can move' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03056(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03056 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'they already control it');
                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 0);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
                $world->game->setControllerForLocation(Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'your claim');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03056', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056, 'x', $foe->Id);
                $claim = $world->theah->queuedOfType(EventLocationClaimed::class)[0];
                Assert::same(2, $claim->playerId, 'they claim');
                Assert::same($foe->Id, $claim->performerId, 'their character');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'no renown resolves');
                Assert::same(['targetChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');

                $world->theah->queuedEvents = [];
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056, 'x', $foe->Id);
                Assert::same('03056_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'renown');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not yet');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056_2, 'x');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'not the source');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'not home');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056_2, 'x', [Game::LOCATION_PLAYER_HOME]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'home');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056_2, 'x', [Game::LOCATION_CITY_FORUM]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0]->location, 'removed');
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $added->location, 'forum');
                Assert::true($added->isMove, 'move');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'an unclaimable location resolves without moving renown' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03056(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03056, 'x', $foe->Id);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no renown step');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
