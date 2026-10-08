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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01081;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01081;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01081_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01081';
    }

    /**
     * Both sides engaged at Docks.
     *
     * @return array{0:_01081,1:Action_01081,2:Character,3:Character} risk, action, our performer, opposing engaged character
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01081(), Game::LOCATION_HAND, 1);
        $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $mine->Engaged = true;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Engaged = true;
        /** @var Action_01081 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $mine, $foe];
    }

    private function trigger(TestWorld $world, Action_01081 $action, int $playerId = 1): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a City Action on a Risk (RiskCityAction) that targets characters' => function () {
                $action = new Action_01081();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when an engaged performer faces an opposing engaged character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the Risk is not in hand (unless override)' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },

            // WHY: a performer who is already en garde has nothing to be put "en garde" - needs an engaged performer.
            'unavailable when our character is en garde (not engaged)' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $mine->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'performer not engaged');
            },

            'unavailable when the opposing character is en garde (not engaged)' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'target not engaged');
            },

            'unavailable when the engaged opposing character is at a different location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not opposing');
            },

            'unavailable when the only engaged character is our own ally at the location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->ControllerId = 1;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ally is not opposing');
            },

            'unavailable when our performer is at Player Home (no friendly in the city)' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $mine->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'unavailable for the opposing player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            'performers are only engaged characters that have an opposing engaged character' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $unopposed = $world->placeCharacter(new GenericCharacter('Unopposed'), Game::LOCATION_CITY_FORUM, 1);
                $unopposed->Engaged = true;
                $enGarde = $world->placeCharacter(new GenericCharacter('En Garde'), Game::LOCATION_CITY_DOCKS, 1);
                $enGarde->Engaged = false;

                $performers = $action->getPerformersForAction(1, $world->theah);

                $ids = array_map(fn($c) => $c->Id, array_values($performers));
                Assert::same([$mine->Id], $ids, 'only the engaged, opposed performer');
            },

            'trigger queues transition 01081 for the triggering player' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $this->trigger($world, $action, 1);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01081', $transitions[0]->transition, 'name');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'player');
                Assert::count(1, $world->theah->queuedEvents, 'only the transition');
            },

            'trigger for a different action id does nothing' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'args list only opposing engaged characters at the performer\'s location' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $enGardeFoe = $world->placeCharacter(new GenericCharacter('En Garde Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $enGardeFoe->Engaged = false;
                $remoteFoe = $world->placeCharacter(new GenericCharacter('Remote Foe'), Game::LOCATION_CITY_FORUM, 2);
                $remoteFoe->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01081, 'highDramaPlayerTurn_01081');

                Assert::same($mine->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['characterIds'], 'only the engaged foe at the performer\'s location');
            },

            'target choice puts both characters en garde in one batch and resolves the action' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01081, 'x', $foe->Id);

                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                Assert::count(2, $engarde, 'both en garde');
                Assert::same($foe->Id, $engarde[0]->cardId, 'target first');
                Assert::same($mine->Id, $engarde[1]->cardId, 'performer second');
                Assert::same($action->Id, $engarde[0]->abilityId, 'ability on target');
                Assert::same($action->Id, $engarde[1]->abilityId, 'ability on performer');
                Assert::same(2, $engarde[0]->playerId, 'target controller');
                Assert::same(1, $engarde[1]->playerId, 'performer controller');
                Assert::true($engarde[0]->batchId > 0, 'batched');
                Assert::same($engarde[0]->batchId, $engarde[1]->batchId, 'same batch (single reaction window)');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(
                    [EventCardEngarded::class, EventCardEngarded::class, EventActionResolved::class],
                    array_map(fn($e) => $e::class, $world->theah->queuedEvents),
                    'order'
                );
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'target choice refuses our own character' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $ally->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01081, 'x', $ally->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'own character refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'target choice refuses a character at another location' => function () {
                $world = new TestWorld();
                [, $action, $mine] = $this->scene($world);
                $remote = $world->placeCharacter(new GenericCharacter('Remote'), Game::LOCATION_CITY_FORUM, 2);
                $remote->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01081, 'x', $remote->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'remote refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'target choice refuses a character that is already en garde' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $foe->Engaged = false;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01081, 'x', $foe->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'already en garde');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'isValidTargetForAbility accepts an engaged opposing character at the performer\'s location' => function () {
                $world = new TestWorld();
                [, $action, $mine, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $mine->Id);
                Assert::true($action->isValidTargetForAbility($world->game, $foe)[0], 'valid');
            },
        ];
    }
}
