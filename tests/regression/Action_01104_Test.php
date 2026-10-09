<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01104;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01104;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01104_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01104';
    }

    /**
     * En garde performer + opposing character at Docks; Amour in hand.
     *
     * @return array{0:_01104,1:Action_01104,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01104(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Alpha'), Game::LOCATION_CITY_DOCKS, 1);
        $mine->Engaged = false;
        $foe = $world->placeCharacter(new GenericCharacter('Beta'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01104 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $mine, $foe];
    }

    private function act(TestWorld $world, Action_01104 $action, int $id): void
    {
        $action->actFromActionWithId(
            $world->game,
            States::HIGH_DRAMA_PLAYER_TURN_01104,
            'highDramaPlayerTurn_01104',
            $id
        );
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that targets characters' => function () {
                $action = new Action_01104();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when an en garde performer faces an opposing character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the only performer is already engaged' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $mine->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when no opposing character shares the location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposition');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $mine->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are only en garde characters with opposition' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_FORUM, 1);
                $lonely->Engaged = false;
                $engaged = $world->placeCharacter(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$mine->Id], $ids, 'only Alpha');
            },

            'trigger queues transition 01104' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01104', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args list opposing characters at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01104,
                    'highDramaPlayerTurn_01104'
                );

                Assert::same($mine->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'only Beta');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far ignored');
            },

            // WHY (journal 2026-10-03-10): act queues ONLY EventCharacterTargeted so Unyielding
            // Loyalty can hold the cancel hook. Engage×2 + Home×2 must not sit beside the hook —
            // UL stores one event and deleteEventBatchs the rest (partial restore / BitW wound).
            'act queues only CharacterTargeted (UL cancel gate), not engages or Home moves' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $this->act($world, $action, $foe->Id);

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'Beta');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage yet');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            // WHY: Surviving CharacterTargeted (Pass / NoD revert) emits the full Amour package
            // with a shared batchId so UL can still sweep if offered again on engage.
            'surviving CharacterTargeted queues engage×2 then Home×2 then ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $risk->Id;
                $targeted->batchId = 42;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(2, $engages, 'both engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'Beta first');
                Assert::same($mine->Id, $engages[1]->cardId, 'Alpha second');
                Assert::same(42, $engages[0]->batchId, 'shared batch');
                Assert::same(42, $engages[1]->batchId, 'shared batch');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'both Home');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::false($moves[0]->engage, 'move does not re-engage');
                Assert::same(42, $moves[0]->batchId, 'batched with engages');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                $firstEngage = array_search(EventCardEngaged::class, $order, true);
                $firstMove = array_search(EventCardMoving::class, $order, true);
                Assert::true($firstEngage < $firstMove, 'engage before Home (Lodestone-safe)');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            // WHY: Successful UL leaves CharacterTargeted canceled — no Amour effects.
            'canceled CharacterTargeted queues no engages, moves, or resolve' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'UL cancel holds effects');
            },

            'act refuses own character' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);
                $threw = false;
                try {
                    $this->act($world, $action, $mine->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'own character');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'act refuses a character at a different location' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);
                $threw = false;
                try {
                    $this->act($world, $action, $far->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'different location');
            },
        ];
    }
}
