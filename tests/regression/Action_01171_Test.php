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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01171;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01171_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01171';
    }

    /**
     * Risk in hand; performer at Docks facing an opposing Mercenary.
     *
     * @return array{0:_01171,1:Action_01171,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01171(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Scoundrel'), Game::LOCATION_CITY_DOCKS, 1);
        $merc = $world->placeCharacter(
            new GenericCharacter('Merc', ['Mercenary']),
            Game::LOCATION_CITY_DOCKS,
            2
        );
        /** @var Action_01171 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $merc];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction that targets characters and requires a performer' => function () {
                $action = new Action_01171();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available with a city performer facing an opposing Mercenary' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the opposing character is not a Mercenary' => function () {
                $world = new TestWorld();
                [, $action, , $merc] = $this->scene($world);
                $merc->Traits = [];
                $merc->ModifiedTraits = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Merc');
            },

            'unavailable when the Mercenary is at a different location' => function () {
                $world = new TestWorld();
                [, $action, , $merc] = $this->scene($world);
                $merc->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'elsewhere');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are only characters with opposing Mercenaries at their location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'only docks');
                Assert::false(in_array($lonely->Id, $ids, true), 'lonely excluded');
            },

            'trigger queues transition 01171' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01171', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'args list opposing Mercenaries at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $allyMerc = $world->placeCharacter(
                    new GenericCharacter('Ally Merc', ['Mercenary']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $far = $world->placeCharacter(
                    new GenericCharacter('Far Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    2
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01171,
                    'highDramaPlayerTurn_01171'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([$merc->Id], $args['ids'], 'only opposing Merc');
                Assert::false(in_array($allyMerc->Id, $args['ids'], true), 'ally excluded');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far excluded');
            },

            // WHY: printed -1 cost while performer is Villain or Scoundrel — discount is
            // gated on this Action id so sticky traits cannot discount unrelated hand Actions.
            'Villain or Scoundrel performer discounts this Action by 1' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Traits = ['Villain'];
                $performer->ModifiedTraits = ['Villain'];
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'Villain');
                Assert::count(1, $explanations, 'explained');

                $scoundrel = new GenericCharacter('Scoundrel', ['Scoundrel']);
                $scoundrelExplanations = [];
                Assert::same(
                    1,
                    $action->getActionFromHandDiscount($world->theah, $scoundrel, $action, $scoundrelExplanations),
                    'Scoundrel'
                );

                $plain = new GenericCharacter('Plain');
                $plainExplanations = [];
                Assert::same(
                    0,
                    $action->getActionFromHandDiscount($world->theah, $plain, $action, $plainExplanations),
                    'plain'
                );
            },

            'unengaged Mercenary is engaged (not moved Home)' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $merc] = $this->scene($world);
                $merc->Engaged = false;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01171,
                    'highDramaPlayerTurn_01171',
                    $merc->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($merc->Id, $engages[0]->cardId, 'merc');
                Assert::same($risk->Id, $engages[0]->sourceId, 'source');
                Assert::same($action->Id, $engages[0]->abilityId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no Home');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                // WHY: act calls bare nextState() — FakeGamestate records null.
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'already-engaged Mercenary moves Home instead of engaging' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $merc] = $this->scene($world);
                $merc->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01171,
                    'highDramaPlayerTurn_01171',
                    $merc->Id
                );

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'Home');
                Assert::same($merc->Id, $moves[0]->cardId, 'merc');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::false($moves[0]->engage, 'no engage on move');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'refuses a non-Mercenary at the same location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01171,
                        'x',
                        $civilian->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'refuses a Mercenary at a different location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $far = $world->placeCharacter(
                    new GenericCharacter('Far Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    2
                );
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01171,
                        'x',
                        $far->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'far refused');
            },
        ];
    }
}
