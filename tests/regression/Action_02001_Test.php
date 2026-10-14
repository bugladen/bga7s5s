<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02001_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02001';
    }

    /**
     * Andriana in city unengaged, Sorcery in hand, enemy non-Leader at another city location.
     *
     * @return array{0:_02001,1:Action_02001,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $andriana = $world->placeCharacter(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
        $sorcery = $world->placeCharacter(new GenericCharacter('Sorcery', ['Sorcery']), Game::LOCATION_HAND, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_02001 $action */
        $action = $andriana->getActions()[0];
        return [$andriana, $action, $sorcery, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer CharacterAction that targets characters' => function () {
                $action = new Action_02001();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcerer');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available in city with Sorcery in hand and enemy non-Leader' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Player Home' => function () {
                $world = new TestWorld();
                [$andriana, $action] = $this->scene($world);
                $andriana->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when Engaged' => function () {
                $world = new TestWorld();
                [$andriana, $action] = $this->scene($world);
                $andriana->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable without Sorcery in hand' => function () {
                $world = new TestWorld();
                [, $action, $sorcery] = $this->scene($world);
                $sorcery->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcery');
            },

            'unavailable when only enemy is a Leader' => function () {
                $world = new TestWorld();
                $andriana = $world->placeCharacter(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Sorcery', ['Sorcery']), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericLeader('Enemy Leader'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Action_02001 $action */
                $action = $andriana->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Leader only');
            },

            'trigger queues transition 02001' => function () {
                $world = new TestWorld();
                [$andriana, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02001', $transitions[0]->transition, 'name');
                Assert::same($andriana->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY (2026-03-16-01): discard choice must validate Sorcery trait server-side —
            // client can send any hand card id.
            'step 1 refuses a non-Sorcery hand card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02001,
                        'x',
                        $plain->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Sorcery required');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 1 accepts Sorcery and stores CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [, $action, $sorcery] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02001,
                    'x',
                    $sorcery->Id
                );

                Assert::same($sorcery->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'next');
            },

            // WHY (2026-03-16-01 / 2026-09-01-03): Leaders are illegal move targets.
            'isValidTargetForAbility rejects Leaders' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_BAZAAR, 2);

                [$ok,] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($ok, 'Leader');
            },

            'isValidTargetForAbility rejects same location and own characters' => function () {
                $world = new TestWorld();
                [$andriana, $action] = $this->scene($world);
                $same = $world->placeCharacter(new GenericCharacter('Same'), Game::LOCATION_CITY_DOCKS, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                [$okSame,] = $action->isValidTargetForAbility($world->game, $same);
                Assert::false($okSame, 'already there');
                [$okAlly,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($okAlly, 'own');
                unset($andriana);
            },

            'isValidTargetForAbility accepts enemy non-Leader elsewhere in city' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'valid');
            },

            // WHY (2026-09-01-03): challenge only if CHOSEN_TARGET ended at Andriana's
            // location after the move (move can be cancelled / redirected).
            'shouldIssueChallenge true only when target shares Andriana location' => function () {
                $world = new TestWorld();
                [$andriana, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                Assert::false($action->shouldIssueChallenge($world->game), 'still at Forum');

                $foe->Location = $andriana->Location;
                Assert::true($action->shouldIssueChallenge($world->game), 'co-located');
            },

            'shouldIssueChallenge false when CHOSEN_TARGET missing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, 999999);
                Assert::false($action->shouldIssueChallenge($world->game), 'missing');
            },

            'step 2 queues move engage discard and Sorcerer bracketing' => function () {
                $world = new TestWorld();
                [$andriana, $action, $sorcery, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $sorcery->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02001_2,
                    'x',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(
                    Game::ANDRIANA_DONDOLOS_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'challenge type'
                );
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat');

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'foe moves');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same($andriana->Location, $moves[0]->toLocation, 'to');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::same('02001_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, '02001_2');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'next');
            },
        ];
    }
}
