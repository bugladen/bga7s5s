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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03045_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03045';
    }

    /**
     * @return array{0:Action_03045,1:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $world->game->globals->set(Game::PLAYER_COUNT, 2);
        $risk = $world->placeCard(new _03045(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_03045 $action */
        $action = $risk->getActions()[0];
        return [$action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a performer risk action' => function () {
                $action = new Action_03045();
                Assert::instanceOf(RiskAction::class, $action, 'risk action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Home is not a destination. The adjacent location must be controlled
            // by an opponent. Uncontrolled and your own claim do not qualify. The trigger
            // only opens the picker.
            'wounds the performer and moves them to an opponent-controlled neighbor' => function () {
                $world = new TestWorld();
                [$action, $performer] = $this->scene($world);
                Assert::same([$performer->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'performer');

                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'your own claim');
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 2);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'opponent claim');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03045', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03045, 'x');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'forum only');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'not home');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03045, 'x', [Game::LOCATION_CITY_FORUM]);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::same($performer->ControllerId, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'resolved');
                Assert::same(['locationChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');
            },

            'an illegal location throws before the wound' => function () {
                $world = new TestWorld();
                [$action] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03045, 'x', [Game::LOCATION_PLAYER_HOME]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'home');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },
        ];
    }
}
