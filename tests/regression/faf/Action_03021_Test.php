<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03021;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03021_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03021';
    }

    /**
     * @return array{0:_03021,1:Action_03021,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03021(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Witch', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_03021 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $sorcerer];
    }

    public function tests(): array
    {
        return [
            'is a city action that must pick a performer' => function () {
                $action = new Action_03021();
                Assert::instanceOf(RiskCityAction::class, $action, 'city action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Engage is paid at announce, before ActionTriggered, so a cancel that
            // deletes the trigger still leaves the engage queued. The trigger sets the
            // Cornered challenge and does not resolve the action.
            'an unengaged performer can challenge an opposing Sorcerer or Monster' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $sorcerer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                Assert::same([$performer->Id], array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'performer');
                [$ok] = $action->isValidTargetForAbility($world->game, $sorcerer);
                Assert::true($ok, 'sorcerer');

                $monster = $world->placeCharacter(new GenericCharacter('Beast', ['Monster']), Game::LOCATION_CITY_DOCKS, 2);
                [$ok] = $action->isValidTargetForAbility($world->game, $monster);
                Assert::true($ok, 'monster');

                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 2);
                [$ok] = $action->isValidTargetForAbility($world->game, $civilian);
                Assert::false($ok, 'no trait');

                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged performer');
                $performer->Engaged = false;

                $action->announceAction($world->game);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage at announce');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(Game::CORNERED_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'cornered');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('03021', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'risk');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'challenge flow resolves it');
            },

            'a target who is not there throws from the validity check' => function () {
                $world = new TestWorld();
                [, $action, , $sorcerer] = $this->scene($world);
                $sorcerer->Location = Game::LOCATION_CITY_FORUM;
                [$ok] = $action->isValidTargetForAbility($world->game, $sorcerer);
                Assert::false($ok, 'different location');
                unset($action);
            },
        ];
    }
}
