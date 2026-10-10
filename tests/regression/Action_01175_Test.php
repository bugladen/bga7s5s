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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01175;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01175;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01175_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01175';
    }

    /**
     * Risk + discard fodder in hand; wounded non-Leader in play.
     *
     * @return array{0:_01175,1:Action_01175,2:GenericCharacter,3:list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card>}
     */
    private function scene(TestWorld $world, int $wounds = 2, int $handExtras = 2): array
    {
        $risk = $world->placeCard(new _01175(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_DOCKS, 1);
        $performer->Wounds = $wounds;
        $hand = [];
        for ($i = 0; $i < $handExtras; $i++) {
            $hand[] = $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
        }
        /** @var Action_01175 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $hand];
    }

    public function tests(): array
    {
        return [
            // WHY (journal 2026-08-04-06): Must be RiskAction — CardAction lacks hand gate and
            // would appear in in-play action scans while discarded.
            'is a RiskAction that targets characters and requires a performer' => function () {
                $action = new Action_01175();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a wounded non-Leader and cards in hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the only wounded character is a Leader' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01175(), Game::LOCATION_HAND, 1);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
                $leader->Wounds = 2;
                $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
                /** @var Action_01175 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Leader only');
            },

            'unavailable when no controlled character is wounded' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'healthy');
                unset($performer);
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are wounded non-Leaders you control (including Home)' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $home = $world->placeCharacter(new GenericCharacter('Home Wounded'), Game::LOCATION_PLAYER_HOME, 1);
                $home->Wounds = 1;
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
                $leader->Wounds = 3;
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'city wounded');
                Assert::true(in_array($home->Id, $ids, true), 'Home wounded');
                Assert::false(in_array($leader->Id, $ids, true), 'Leader excluded');
                Assert::false(in_array($healthy->Id, $ids, true), 'healthy excluded');
            },

            'trigger queues transition 01175' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01175', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: discard events first, then heal equal to discard count, then ActionResolved.
            'discards chosen hand cards then heals that many wounds' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $hand] = $this->scene($world, 2, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01175,
                    'highDramaPlayerTurn_01175',
                    [$hand[0]->Id, $hand[1]->Id]
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'two discards');
                Assert::same($hand[0]->Id, $discards[0]->cardId, 'first');
                Assert::same($hand[1]->Id, $discards[1]->cardId, 'second');
                Assert::same($risk->Id, $discards[0]->sourceId, 'source');

                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heals, 'heal');
                Assert::same($performer->Id, $heals[0]->characterId, 'performer');
                Assert::same(2, $heals[0]->wounds, 'two wounds');
                Assert::same($risk->Id, $heals[0]->sourceId, 'heal source');
                Assert::same($action->Id, $heals[0]->abilityId, 'ability');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventCardDiscardedFromHand::class, $order, true)
                        < array_search(EventCharacterBeingHealed::class, $order, true),
                    'discard before heal'
                );
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'refuses discarding more cards than the performer has wounds' => function () {
                $world = new TestWorld();
                [, $action, $performer, $hand] = $this->scene($world, 1, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01175,
                        'x',
                        [$hand[0]->Id, $hand[1]->Id]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'wound cap');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'refuses a card not in hand' => function () {
                $world = new TestWorld();
                [, $action, $performer, $hand] = $this->scene($world, 2, 2);
                $hand[0]->Location = Game::LOCATION_CITY_FORUM;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01175,
                        'x',
                        [$hand[0]->Id]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
            },

            'refuses a card owned by an opponent' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 2, 0);
                $foeCard = $world->placeCard(new _01171(), Game::LOCATION_HAND, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01175,
                        'x',
                        [$foeCard->Id]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'opponent card');
            },

            'isValidTargetForAbility rejects Leaders and unwounded characters' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
                $leader->Wounds = 2;
                $healthy = $world->placeCharacter(new GenericCharacter('Healthy'), Game::LOCATION_CITY_DOCKS, 1);

                [$okLeader,] = $action->isValidTargetForAbility($world->game, $leader);
                [$okHealthy,] = $action->isValidTargetForAbility($world->game, $healthy);
                Assert::false($okLeader, 'Leader');
                Assert::false($okHealthy, 'healthy');
            },
        ];
    }
}
