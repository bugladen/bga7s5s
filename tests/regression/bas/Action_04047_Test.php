<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04047_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04047';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage is paid at announce so a later cancel still paid the cost.
            // A second announce while already engaged does not engage again. The type is
            // Celerity (finesse, intervene gate only) and the action is not resolved here.
            'engages the duelist at announce and opens a finesse challenge' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04047(), Game::LOCATION_HAND, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04047 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'duelist with a target');
                $ids = array_map(fn($character) => $character->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$duelist->Id], array_values($ids), 'hero hidden');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $duelist->Id);
                $action->announceAction($world->game);
                Assert::same($duelist->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');

                $world->theah->takeQueuedEvents();
                $duelist->Engaged = true;
                $action->announceAction($world->game);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'already engaged');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged hides it');

                $duelist->Engaged = false;
                $duelist->DashedFinesse = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed finesse');
                $duelist->DashedFinesse = false;

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same(Game::CELERITY_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'celerity');
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'finesse');
                Assert::same('04047', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'resolved with the challenge');

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposing character');
            },
        ];
    }
}
