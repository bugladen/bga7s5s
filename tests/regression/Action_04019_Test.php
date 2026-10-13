<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04019_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04019';
    }

    /** @return array{0:_04019,1:Action_04019,2:Character,3:Character,4:_01048} */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _04019(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Rena'), Game::LOCATION_CITY_DOCKS, 1);
        $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $weapon->AttachedToId = $performer->Id;
        $weapon->Engaged = false;
        $performer->Attachments[] = $weapon->Id;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Action_04019 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe, $weapon];
    }

    public function tests(): array
    {
        return [
            'available with engarde performer, Melee Weapon, and opposing character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performers');
            },

            'unavailable when performer is Engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged performer');
            },

            'unavailable without eligible attachment' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04019(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Rena'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_04019 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no weapon');
            },

            'trigger queues attachment chooser' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('04019', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY regression: NO_MORE_WORDS was wrongly ON stIssueChallenge auto-engage,
            // so Rena engaged with her weapon. Print engages the attachment only.
            'attachment confirm engages weapon only and mints type off auto-engage' => function () {
                $world = new TestWorld();
                [, $action, $performer, , $weapon] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_04019,
                    'highDramaPhase04019',
                    $weapon->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'one engage');
                Assert::same($weapon->Id, $engages[0]->cardId, 'weapon only');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat');
                Assert::same(
                    Game::NO_MORE_WORDS_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'type off auto-engage'
                );
                Assert::same('04019_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'to choose-target');
            },
        ];
    }
}
