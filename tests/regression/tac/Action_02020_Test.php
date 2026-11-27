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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02020;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02020_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02020';
    }

    /**
     * Yield in hand; performer with eligible attachment; opposing non-Leader at Docks.
     *
     * @return array{0:_02020,1:Action_02020,2:GenericCharacter,3:GenericCharacter,4:mixed}
     */
    private function scene(TestWorld $world, string $attachmentKind = 'panzerhand'): array
    {
        $risk = $world->placeCard(new _02020(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        if ($attachmentKind === 'panzerhand') {
            $attachment = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 1);
        } else {
            $attachment = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        }
        $attachment->AttachedToId = $performer->Id;
        $performer->Attachments[] = $attachment->Id;

        /** @var Action_02020 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe, $attachment];
    }

    private function targeted(TestWorld $world, Action_02020 $action, GenericCharacter $target, _02020 $risk, int $batchId): EventCharacterTargeted
    {
        $event = new EventCharacterTargeted();
        $event->abilityId = $action->Id;
        $event->targetId = $target->Id;
        $event->sourceId = $risk->Id;
        $event->batchId = $batchId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that targets characters and requires performer' => function () {
                $action = new Action_02020();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Panzerhand is Armor+Eisenfaust (not Weapon) — Eisenfaust alone qualifies.
            'available with Panzerhand Eisenfaust and opposing non-Leader' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 'panzerhand');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'panzerhand');
            },

            'available with Melee Weapon attachment' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 'melee');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'melee');
            },

            'unavailable when attachment is Engaged' => function () {
                $world = new TestWorld();
                [, $action, , , $attachment] = $this->scene($world);
                $attachment->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable without opposing non-Leader at location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02020(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $panzer = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 1);
                $panzer->AttachedToId = $performer->Id;
                $performer->Attachments[] = $panzer->Id;
                $world->placeCharacter(new GenericLeader('Enemy Leader'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_02020 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader only');
            },

            // WHY: Engaged targets remain valid — auto-wound later (no en-garde filter).
            'Engaged opposing non-Leader remains a valid target' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $foe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'engaged ok');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'still available');
            },

            'isValidTargetForAbility rejects Leaders' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                [$ok,] = $action->isValidTargetForAbility($world->game, $leader);
                Assert::false($ok, 'leader');
            },

            'trigger queues transition 02020' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02020', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'args step 1 lists opposing non-Leaders at performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02020,
                    'highDramaPlayerTurn_02020'
                );

                Assert::same([$foe->Id], $args['ids'], 'foe only');
                Assert::false(in_array($leader->Id, $args['ids'], true), 'no leader');
            },

            'act step 1 stores target in CHOSEN_CARD and transitions characterChosen' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02020,
                    'highDramaPlayerTurn_02020',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'target');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named');
            },

            'act step 1 rejects Leaders' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02020,
                        'highDramaPlayerTurn_02020',
                        $leader->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            // WHY: attachment engage is the paid cost (queued unbatched); the engage-or-wound
            // resolution waits for a surviving EventCharacterTargeted.
            'act step 2 engages attachment (cost) and queues only CharacterTargeted for the effect' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe, $attachment] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02020_2,
                    'highDramaPlayerTurn_02020_2',
                    $attachment->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage attachment cost');
                Assert::same($attachment->Id, $engages[0]->cardId, 'attachment');
                Assert::true(empty($engages[0]->batchId), 'cost is outside the targeting batch');
                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'foe');
                Assert::same($risk->Id, $targeted[0]->sourceId, 'source');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no opponent choice yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'named');
            },

            // WHY already Engaged → auto-wound: print has no en-garde filter.
            'surviving CharacterTargeted auto-wounds an Engaged target with the shared batch' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);
                $foe->Engaged = true;

                $action->handleEvent($this->targeted($world, $action, $foe, $risk, 77));

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'auto wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same(77, $wounds[0]->batchId, 'shares batch');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no opponent choice');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'surviving CharacterTargeted transitions 02020_2 when target is en garde' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);

                $action->handleEvent($this->targeted($world, $action, $foe, $risk, 88));

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no auto wound');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'opponent choice');
                Assert::same('02020_2', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'opponent');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source owner card');
                Assert::same(88, $transitions[0]->batchId, 'shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'canceled or foreign CharacterTargeted queues nothing' => function () {
                $world = new TestWorld();
                [$risk, $action, , $foe] = $this->scene($world);

                $canceled = $this->targeted($world, $action, $foe, $risk, 5);
                $canceled->canceled = true;
                $action->handleEvent($canceled);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');

                $other = $this->targeted($world, $action, $foe, $risk, 6);
                $other->abilityId = 'other';
                $action->handleEvent($other);
                Assert::count(0, $world->theah->queuedEvents, 'other ability');
            },

            'act step 3 id 1 engages the target' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02020_3,
                    'highDramaPlayerTurn_02020_3',
                    1
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'target');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
                Assert::same([null], $world->game->gamestate->transitions, 'bare next');
            },

            'act step 3 id 2 wounds the target' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02020_3,
                    'highDramaPlayerTurn_02020_3',
                    2
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
            },
        ];
    }
}
