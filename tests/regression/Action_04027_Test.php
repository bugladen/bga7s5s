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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04027_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04027';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde Diplomat action. The target click does not resolve. Engage
            // is the target's choice. Decline claims only when the location can be claimed.
            // A Leader is never a legal target, even if the action was already opened.
            'target may engage, or you claim when they refuse' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
                $diplomat = $world->placeCharacter(new _04022(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $leader = $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04027 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready diplomat');
                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027, '')['ids'];
                Assert::same([$foe->Id], $ids, 'leader excluded');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027, '', $foe->Id);
                Assert::same('04027_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'their choice');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'not resolved yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'no resolve');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027_2, '', 1);
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'they engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027_2, '', 2);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'claimed');

                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027_2, '', 2);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'unclaimable still resolves');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved anyway');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04027, '', $leader->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'leader');

                $diplomat->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged diplomat');
            },
        ];
    }
}
