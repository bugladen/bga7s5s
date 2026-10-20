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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04057;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04057_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04057';
    }

    public function tests(): array
    {
        return [
            // WHY: You must control fewer characters than the target's controller.
            // The performer is any en garde Leader in the city, not only the player leader.
            // Dashed stats are omitted. Id 1 is Combat and does not resolve the action.
            // The type stays off auto-engage.
            'issues a chosen-stat challenge to a non-leader' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04057(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $extra = $world->placeCharacter(new GenericCharacter('Extra'), Game::LOCATION_CITY_FORUM, 2);
                $theirLeader = $world->placeCharacter(new GenericLeader('Their Leader'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04057 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'fewer characters');
                $stats = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04057, '')['stats'];
                Assert::same([Game::STAT_COMBAT, Game::STAT_FINESSE, Game::STAT_INFLUENCE], $stats, 'all three');

                $leader->DashedCombat = true;
                $stats = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04057, '')['stats'];
                Assert::false(in_array(Game::STAT_COMBAT, $stats, true), 'dashed combat');
                $leader->DashedCombat = false;

                [$leaderTarget] = $action->isValidTargetForAbility($world->game, $theirLeader);
                Assert::false($leaderTarget, 'leaders are not targets');
                [$ok] = $action->isValidTargetForAbility($world->game, $target);
                Assert::true($ok, 'non-leader of the larger side');

                $extra->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'counts are equal');
                $extra->ControllerId = 2;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');

                $leader->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader at home');
                $leader->Location = Game::LOCATION_CITY_DOCKS;

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $world->fireOn($risk, $triggered);
                Assert::same('04057', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choose stat');

                $bad = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04057, '', 4);
                } catch (UserException) {
                    $bad = true;
                }
                Assert::true($bad, 'not a stat');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04057, '', 1);
                Assert::same(Game::HONORABLE_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('04057_2', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'choose target');
                Assert::same('statChosen', end($world->game->gamestate->transitions), 'stat click waits');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
            },
        ];
    }
}
