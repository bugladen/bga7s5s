<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05DabneyUS01;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
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

            // WHY: L→R — move is legal with an adjacent city even when no challenge target exists.
            'available when adjacent city exists with no opposing characters' => function () {
                $world = new TestWorld();
                $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_DOCKS, 1);
                $action = $valeri->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'empty adjacent ok');
            },

            'available when an enemy is at an adjacent city location' => function () {
                $world = new TestWorld();
                [, , $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'adjacent foe');
            },

            // WHY: PTv0.4 print — "opposing non-Leader". Leaders alone do not block availability
            // (move still legal); they only fail the challenge clause after the move.
            'available when the only adjacent enemy is a Leader' => function () {
                $world = new TestWorld();
                $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), Game::LOCATION_CITY_FORUM, 2);
                $action = $valeri->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'leader-only adjacent still moveable');
            },

            'isValidTargetForAbility rejects Leaders and accepts co-located non-Leaders' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $action] = $this->scene($world);
                $foe->Location = $valeri->Location;
                $boss = $world->placeCharacter(new GenericCharacter('Boss', ['Leader']), $valeri->Location, 2);

                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'non-leader ok');
                [$ok] = $action->isValidTargetForAbility($world->game, $boss);
                Assert::false($ok, 'leader rejected');
            },

            'isValidTargetForAbility rejects characters not at Valeri location' => function () {
                $world = new TestWorld();
                [, $foe, $action] = $this->scene($world);
                // Foe still at Forum; Valeri at Docks — post-move gate.
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($ok, 'not co-located');
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

            // WHY: Diplomatic Envoy bans Combat vs En Garde Forum Diplomat. Co-location alone
            // was true → Technique picker → Pass called stIssueChallenge → UserException soft-lock
            // (Back blocked for VALERI_CHALLENGE_TYPE). shouldIssueChallenge must refuse.
            'shouldIssueChallenge false when Diplomatic Envoy bans Combat vs En Garde Forum Diplomat' => function () {
                $world = new TestWorld();
                $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_FORUM, 1);
                $diplomat = $world->placeCharacter(
                    new GenericCharacter('Envoy', ['Diplomat']),
                    Game::LOCATION_CITY_FORUM,
                    2
                );
                $world->placeCard(new _04024(), Game::LOCATION_PLAYER_HOME, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $diplomat->Id);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                /** @var Action_05DabneyUS01 $action */
                $action = $valeri->getActions()[0];

                Assert::false($action->shouldIssueChallenge($world->game), 'envoy ban');

                $diplomat->Engaged = true;
                Assert::true($action->shouldIssueChallenge($world->game), 'engaged diplomat ok');
            },

            // WHY: Envoy-only adjacent no longer blocks availability — move still legal;
            // challenge clause fails later (stateFromAction / shouldIssueChallenge).
            'available when the only adjacent foe is an Envoy-protected Diplomat' => function () {
                $world = new TestWorld();
                $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Envoy', ['Diplomat']),
                    Game::LOCATION_CITY_FORUM,
                    2
                );
                $world->placeCard(new _04024(), Game::LOCATION_PLAYER_HOME, 2);
                $action = $valeri->getActions()[0];

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'envoy-only still moveable');
            },

            'location pick queues move without engage' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01,
                    'highDramaPhase05DabneyUS01',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($valeri->Id, $moves[0]->cardId, 'Valeri moves');
                Assert::same(false, $moves[0]->engage, 'no engage printed');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to Forum');
                Assert::same($valeri->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
            },

            'EventCardMoved for Valeri queues 05DabneyUS01_3 bridge' => function () {
                $world = new TestWorld();
                [$valeri, , $action] = $this->scene($world);

                $moved = new EventCardMoved();
                $moved->theah = $world->theah;
                $moved->abilityId = $action->Id;
                $moved->cardId = $valeri->Id;
                $moved->toLocation = Game::LOCATION_CITY_FORUM;
                $moved->canceled = false;
                $action->handleEvent($moved);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('05DabneyUS01_3', $transitions[0]->transition, 'post-move bridge');
            },

            // WHY: Bridge runs after CardMoved reactions; empty destination → ActionResolved.
            'stateFromAction noTargets queues ActionResolved' => function () {
                $world = new TestWorld();
                $valeri = $world->placeCharacter(new _05DabneyUS01(), Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_05DabneyUS01 $action */
                $action = $valeri->getActions()[0];

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_3,
                    'highDramaPhase05DabneyUS01_3'
                );

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
            },

            'EventCharacterTargeted stamps VALERI_CHALLENGE_TYPE and queues 05DabneyUS01_2' => function () {
                $world = new TestWorld();
                [$valeri, $foe, $action] = $this->scene($world);
                $foe->Location = $valeri->Location;

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
                Assert::count(0, $moves, 'no move on targeting — already moved');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'technique hub-exit');
                Assert::same('05DabneyUS01_2', $transitions[0]->transition, 'challenge hub-exit');
            },
        ];
    }
}
