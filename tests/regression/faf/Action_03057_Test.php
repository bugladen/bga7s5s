<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03057_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03057';
    }

    public function tests(): array
    {
        return [
            'is a risk city action that needs a performer' => function () {
                $action = new Action_03057();
                Assert::instanceOf(RiskCityAction::class, $action, 'risk city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Engage is paid in announce, before the trigger, and the type stays off
            // auto-engage so it is not paid twice. An already engaged performer is not a choice.
            // The trigger does not resolve the action.
            'engages the performer and issues an influence challenge' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03057(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03057 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $performer->Engaged = false;
                $performer->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed influence');
                $performer->DashedInfluence = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $action->announceAction($world->game);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');

                $world->theah->queuedEvents = [];
                $performer->Engaged = true;
                $action->announceAction($world->game);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no second engage');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(Game::CENSURE_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'censure');
                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'influence');
                Assert::same('03057', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
            },
        ];
    }
}
