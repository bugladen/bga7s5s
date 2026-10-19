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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Action_04039_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04039';
    }

    public function tests(): array
    {
        return [
            // WHY: Zealot, Academic, or Bard is -1. Engage is paid at announce, before
            // the effect, and is not repeated on confirm. Confirm loses a controlled
            // location and claims the performer's location. No controlled location, an
            // engaged performer, or an unclaimable location hides the action.
            'engages at announce, then yields one location and claims another' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                $zealot = $world->placeCharacter(new _04031(), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04039 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $zealot->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'can lose the docks and claim the forum');

                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $zealot, $action, $explanations), 'zealot');
                $academic = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $academic, $action, $explanations), 'academic');
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $hero, $action, $explanations), 'hero');

                $action->announceAction($world->game);
                Assert::same($zealot->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged at announce');
                Assert::count(1, $world->theah->queuedOfType(EventActionActivated::class), 'then activated');

                $world->theah->takeQueuedEvents();
                $zealot->Engaged = true;
                $action->announceAction($world->game);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'does not engage twice');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04039, '', [Game::LOCATION_CITY_DOCKS]);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationBecomesUncontrolled::class)[0]->location, 'yielded');
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'claimed');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04039, '', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'you do not control the bazaar');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to yield');
            },
        ];
    }
}
