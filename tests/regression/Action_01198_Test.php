<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01198;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01198;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01198_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01198';
    }

    /** @return array{0:_01198,1:GenericCharacter,2:GenericCharacter,3:Action_01198} */
    private function armed(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), $location, 1);
        $tri = $world->placeCard(new _01198(), $location, 1);
        $tri->AttachedToId = $host->Id;
        $host->Attachments[] = $tri->Id;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), $location, 2);
        /** @var Action_01198 $action */
        $action = $tri->getActions()[0];
        return [$tri, $host, $foe, $action];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction that targets characters' => function () {
                $action = new Action_01198();
                Assert::instanceOf(AttachmentAction::class, $action, 'AttachmentAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when equipped host is ready in city with an opposing character' => function () {
                $world = new TestWorld();
                [, , , $action] = $this->armed($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when no opposing character is present' => function () {
                $world = new TestWorld();
                [, , $foe, $action] = $this->armed($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no foe');
            },

            'unavailable when host is engaged' => function () {
                $world = new TestWorld();
                [, $host, , $action] = $this->armed($world);
                $host->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when host is Home' => function () {
                $world = new TestWorld();
                [, $host, , $action] = $this->armed($world);
                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable when unattached' => function () {
                $world = new TestWorld();
                $tri = $world->placeCard(new _01198(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01198 $action */
                $action = $tri->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            // WHY (journal 2026-04-08-05): server must reject same-controller targets.
            'isValidTargetForAbility rejects ally and off-location foe, accepts local foe' => function () {
                $world = new TestWorld();
                [, $host, $foe, $action] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                Assert::false($action->isValidTargetForAbility($world->game, $ally)[0], 'ally');
                Assert::false($action->isValidTargetForAbility($world->game, $far)[0], 'far');
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'foe');
            },

            'trigger sets Finesse TRISKELION challenge, performer, and transition 01198' => function () {
                $world = new TestWorld();
                [$tri, $host, , $action] = $this->armed($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'Finesse');
                Assert::same(Game::TRISKELION_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same($host->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01198', $transitions[0]->transition, 'name');
                Assert::same($tri->Id, $transitions[0]->sourceId, 'source');
            },

            'eventCheck blocks ChallengeRejected by a non-Leader under Triskelion' => function () {
                $world = new TestWorld();
                [$tri, $host, $foe, $action] = $this->armed($world);
                $tri->AttachedToId = $host->Id;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::TRISKELION_CHALLENGE_TYPE);

                $event = new EventChallengeRejected();
                $event->challengerId = $host->Id;
                $event->targetId = $foe->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $action->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'non-Leader refuse blocked');
            },

            'eventCheck allows ChallengeRejected by a Leader under Triskelion' => function () {
                $world = new TestWorld();
                [$tri, $host, , $action] = $this->armed($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $tri->AttachedToId = $host->Id;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::TRISKELION_CHALLENGE_TYPE);

                $event = new EventChallengeRejected();
                $event->challengerId = $host->Id;
                $event->targetId = $leader->Id;
                $event->theah = $world->theah;

                $action->eventCheck($event);
                Assert::true(true, 'Leader may refuse');
            },
        ];
    }
}
