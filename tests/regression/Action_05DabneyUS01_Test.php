<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05DabneyUS01_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05DabneyUS01';
    }

    /**
     * CAD Valeri at Docks; enemy at Forum (adjacent in 2-player map).
     *
     * @return array{0:_05DabneyUS01,1:GenericCharacter,2:Action_05DabneyUS01}
     */
    private function scene(TestWorld $world): array
    {
        $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_05DabneyUS01 $action */
        $action = $valeri->getActions()[0];
        return [$valeri, $foe, $action];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_05DabneyUS01();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when an enemy is at an adjacent city location' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent foe');
            },

            // WHY: No Engage printed — engaged Valeri remains eligible (trichotomy c).
            'available when Valeri is engaged' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);
                $valeri->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged ok');
            },

            // WHY: Soline Reaction_03040 (and kin) can leave after Valeri lands; challenge
            // needs same location. Mirror Andriana Action_02001::shouldIssueChallenge.
            'shouldIssueChallenge true only when target shares Valeri location' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                Assert::false($action->shouldIssueChallenge($world->game), 'still at Forum');

                $foe->Location = $valeri->Location;
                Assert::true($action->shouldIssueChallenge($world->game), 'co-located');
            },

            'shouldIssueChallenge false when CHOSEN_TARGET missing' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, 999999);
                Assert::false($action->shouldIssueChallenge($world->game), 'missing');
            },

            'EventCharacterTargeted queues move without engage and stamps VALERI_CHALLENGE_TYPE' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $action] = $this->scene($world);

                $targeted = new EventCharacterTargeted();
                $targeted->theah = $world->theah;
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = false;
                $action->handleEvent($targeted);

                Assert::same($valeri->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::VALERI_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($valeri->Id, $moves[0]->cardId, 'Valeri moves');
                Assert::same(false, $moves[0]->engage, 'no engage printed');
                Assert::same($foe->Location, $moves[0]->toLocation, 'to target');
            },

            'EventCardMoved for Valeri queues 05DabneyUS01_2' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);

                $moved = new EventCardMoved();
                $moved->theah = $world->theah;
                $moved->abilityId = $action->Id;
                $moved->cardId = $valeri->Id;
                $moved->canceled = false;
                $action->handleEvent($moved);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('05DabneyUS01_2', $transitions[0]->transition, 'challenge hub-exit');
            },
        ];
    }
}
