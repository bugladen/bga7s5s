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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01156;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01156;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01156_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01156';
    }

    /**
     * Musket on host at Docks; adjacent foe at Forum; optional hand card for discard cost.
     *
     * @return array{0:_01156,1:Action_01156,2:GenericCharacter,3:GenericCharacter,4:?\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card}
     */
    private function scene(TestWorld $world, bool $withHand = true): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $musket = $world->placeCard(new _01156(), Game::LOCATION_CITY_DOCKS, 1);
        $musket->AttachedToId = $host->Id;
        $host->Attachments[] = $musket->Id;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        $hand = null;
        if ($withHand) {
            $hand = $world->placeCard(new _01159(), Game::LOCATION_HAND, 1);
        }
        /** @var Action_01156 $action */
        $action = $musket->getActions()[0];
        return [$musket, $action, $host, $foe, $hand];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction that targets characters and is ranged' => function () {
                $action = new Action_01156();
                Assert::instanceOf(AttachmentAction::class, $action, 'AttachmentAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::instanceOf(IRangedAbility::class, $action, 'ranged');
            },

            'available with hand, city host, and adjacent opposing character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with empty hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no hand');
            },

            // WHY (2p): same-location foes do not count — musket requires adjacent City location.
            'unavailable when the only foe is at the same location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'same location');
            },

            'unavailable when host is at Player Home' => function () {
                $world = new TestWorld();
                [$musket, $action, $host] = $this->scene($world);
                $host->Location = Game::LOCATION_PLAYER_HOME;
                $musket->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'trigger queues transition 01156' => function () {
                $world = new TestWorld();
                [$musket, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01156', $transitions[0]->transition, 'discard step');
                Assert::same($musket->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: Katain copies effects only — skip discard cost and jump to target picker.
            'effects-only copy trigger queues 01156_2' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->IsEffectsOnlyCopy = true;

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01156_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'skip discard');
            },

            'discard step parks hand card and advances via cardChosen' => function () {
                $world = new TestWorld();
                [, $action, , , $hand] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156,
                    'highDramaPlayerTurn_01156',
                    $hand->Id
                );

                Assert::same([$hand->Id], $world->game->parkedCardIds, 'parked');
                Assert::same($hand->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'named');
            },

            'args for target step list adjacent opposing characters' => function () {
                $world = new TestWorld();
                [, $action, $host, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_2,
                    'highDramaPlayerTurn_01156_2'
                );

                Assert::same($host->Id, $args['performerId'], 'performer');
                Assert::true(in_array($foe->Id, $args['charactersIds'], true), 'adjacent foe');
                Assert::false(in_array($far->Id, $args['charactersIds'], true), 'far');
                Assert::false(in_array($ally->Id, $args['charactersIds'], true), 'ally');
            },

            'selecting an already-engaged target discards, wounds, fires ranged, and resolves' => function () {
                $world = new TestWorld();
                [$musket, $action, , $foe, $hand] = $this->scene($world);
                $foe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_CARD, $hand->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_2,
                    'highDramaPlayerTurn_01156_2',
                    $foe->Id
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'hand card');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($musket->Id, $wounds[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            // WHY: effects-only must not invent a discard from CHOSEN_CARD.
            'effects-only engaged path skips discard but still wounds' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Engaged = true;
                $action->IsEffectsOnlyCopy = true;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_2,
                    'highDramaPlayerTurn_01156_2',
                    $foe->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
            },

            'selecting an en garde target queues discard and opponent 01156_3 choice' => function () {
                $world = new TestWorld();
                [$musket, $action, , $foe, $hand] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $hand->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_2,
                    'highDramaPlayerTurn_01156_2',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'choice');
                Assert::same('01156_3', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'foe controller');
                Assert::same($musket->Id, $transitions[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
            },

            'opponent chooses Engage' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_3,
                    'highDramaPlayerTurn_01156_3',
                    1
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'foe');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'opponent refuses and takes a wound' => function () {
                $world = new TestWorld();
                [$musket, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01156_3,
                    'highDramaPlayerTurn_01156_3',
                    2
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same($musket->Id, $wounds[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
            },

            'isValidTargetForAbility rejects own characters and non-adjacent foes' => function () {
                $world = new TestWorld();
                [, $action, $host, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);

                [$okOwn, ] = $action->isValidTargetForAbility($world->game, $host);
                Assert::false($okOwn, 'own');
                [$okFar, ] = $action->isValidTargetForAbility($world->game, $far);
                Assert::false($okFar, 'far');
                [$okFoe, ] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($okFoe, 'adjacent foe');
            },

            'refuses targeting a non-adjacent character in step 2' => function () {
                $world = new TestWorld();
                [, $action, , , $hand] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_BAZAAR, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, $hand->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01156_2,
                        'highDramaPlayerTurn_01156_2',
                        $far->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'state constants registered' => function () {
                Assert::same(401156, States::HIGH_DRAMA_PLAYER_TURN_01156, '01156');
                Assert::same(4011562, States::HIGH_DRAMA_PLAYER_TURN_01156_2, '01156_2');
                Assert::same(4011563, States::HIGH_DRAMA_PLAYER_TURN_01156_3, '01156_3');
            },
        ];
    }
}
