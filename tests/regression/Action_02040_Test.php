<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;

class Action_02040_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02040';
    }

    /**
     * @return array{0:_02040,1:Action_02040,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $wounds = 1): array
    {
        $risk = $world->placeCard(new _02040(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Wounds = $wounds;
        /** @var Action_02040 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    private function trigger(TestWorld $world, Action_02040 $action, int $performerId): void
    {
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performerId);
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that requires a performer' => function () {
                $action = new Action_02040();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available while the Risk is in hand and you have a character in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one');
                Assert::same($performer->Id, $performers[0]->Id, 'performer');
            },

            'unavailable when the Risk is not in hand or nobody is in the city' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');

                $risk->Location = Game::LOCATION_HAND;
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody in city');
            },

            'unavailable when Used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            // Move Home engaged, then heal one wound.
            'trigger moves the performer Home engaged and heals a wound' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world, 2);

                $this->trigger($world, $action, $performer->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'home');
                Assert::true($moves[0]->engage, 'engaged');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($performer->Id, $heals[0]->characterId, 'who');
                Assert::same(1, $heals[0]->wounds, 'one');
                Assert::same($risk->Id, $heals[0]->sourceId, 'source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'does not heal when the performer has no wounds' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 0);

                $this->trigger($world, $action, $performer->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCardMoving::class), 'still moves');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'ignores a different action id' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = 'other';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
