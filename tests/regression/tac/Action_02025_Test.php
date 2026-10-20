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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02025;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02025_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02025';
    }

    /**
     * Scheme at Home; Diplomat + opposing Influence<=Diplomat at Docks.
     *
     * @return array{0:_02025,1:Action_02025,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, int $foeInfluence = 1): array
    {
        /** @var _02025 $scheme */
        $scheme = $world->placeCard(new _02025(), Game::LOCATION_PLAYER_HOME, 1);
        $diplomat = $world->placeCharacter(
            new GenericCharacter('Diplomat', ['Diplomat']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $diplomat->Influence = 2;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Influence = $foeInfluence;
        /** @var Action_02025 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $diplomat, $foe];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction that targets characters and requires performer' => function () {
                $action = new Action_02025();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'state constants registered' => function () {
                Assert::same(402025, States::HIGH_DRAMA_PLAYER_TURN_02025, 'step 1');
                Assert::same(4020252, States::HIGH_DRAMA_PLAYER_TURN_02025_2, 'step 2');
            },

            // WHY: Diplomat City Action — available with Diplomat vs equal/lower Influence + adjacent.
            'available with city Diplomat and eligible opposing target' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Diplomat' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02025(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_02025 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no diplomat');
            },

            'unavailable when opposing Influence exceeds Diplomat' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 5);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'high influence');
            },

            'getPerformersForAction lists only Diplomats' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($diplomat->Id, $ids, true), 'diplomat');
                Assert::false(in_array($plain->Id, $ids, true), 'plain');
            },

            'isValidTargetForAbility rejects higher Influence and own characters' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $foe] = $this->scene($world, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
                $high = $world->placeCharacter(new GenericCharacter('High'), Game::LOCATION_CITY_DOCKS, 2);
                $high->Influence = 5;
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'foe');
                [$ok,] = $action->isValidTargetForAbility($world->game, $high);
                Assert::false($ok, 'high');
                [$ok,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ok, 'own');
            },

            'trigger queues transition 02025' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02025', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'act character stores target and transitions characterChosen' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02025,
                    'highDramaPlayerTurn_02025',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named');
            },

            // WHY: move target+performer+renown (if >0) to adjacent; shared batchId; ActionResolved.
            'act location moves target, performer, and Renown with shared batchId' => function () {
                $world = new TestWorld();
                [$scheme, $action, $diplomat, $foe] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02025_2,
                    'highDramaPlayerTurn_02025_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'two moves');
                $moveIds = array_map(fn($m) => $m->cardId, $moves);
                Assert::true(in_array($foe->Id, $moveIds, true), 'foe');
                Assert::true(in_array($diplomat->Id, $moveIds, true), 'diplomat');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($moves[0]->batchId, $moves[1]->batchId, 'shared batch');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'source');

                Assert::count(1, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'moving');
                Assert::count(1, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'removed');
                Assert::count(1, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'added');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            'act location skips Renown move when source has zero' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $foe] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02025_2,
                    'highDramaPlayerTurn_02025_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::count(2, $world->theah->queuedOfType(EventCardMoving::class), 'moves');
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'no renown');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act character rejects higher Influence' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $high = $world->placeCharacter(new GenericCharacter('High'), Game::LOCATION_CITY_DOCKS, 2);
                $high->Influence = 5;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02025,
                        'highDramaPlayerTurn_02025',
                        $high->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
