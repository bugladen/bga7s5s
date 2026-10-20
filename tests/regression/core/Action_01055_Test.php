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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01055_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01055';
    }

    private function equip(TestWorld $world, Character $host, object $attachment): void
    {
        $placed = $world->placeCard($attachment, Game::LOCATION_CITY_DOCKS, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
    }

    /** @return array{0:_01055,1:Action_01055,2:Character,3:Character} armed performer at Docks vs one foe */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Shooter'), Game::LOCATION_CITY_DOCKS, 1);
        $this->equip($world, $performer, new _01049());
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Action_01055 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'is a Ranged ability' => function () {
                Assert::instanceOf(IRangedAbility::class, new Action_01055(), 'IRangedAbility');
            },

            'available with Ranged Weapon performer facing opposing character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'performers');
            },

            'unavailable without Ranged Weapon' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Bare'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01055 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no weapon');
            },

            'unavailable with non-Weapon attachment' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
                $host = $world->placeCharacter(new GenericCharacter('Armored'), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $host, new _01047());
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Action_01055 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'armor only');
            },

            'unavailable without an opposing character at the performer location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
                $shooter = $world->placeCharacter(new GenericCharacter('Shooter'), Game::LOCATION_CITY_DOCKS, 1);
                $this->equip($world, $shooter, new _01049());
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Action_01055 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no one to move');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger queues transition 01055' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01055', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'getArgs step 1 lists opposing characters at performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01055, 'x');
                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['characterIds'], 'only opposing at location');
            },

            'getArgs step 2 lists adjacent City locations without Home' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01055_2, 'x');
                Assert::same($foe->Id, $args['targetId'], 'target');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'docks neighbours (2 players)');
                Assert::false(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'no Home');
            },

            'step 1 selecting opposing character stores target and transitions' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01055, 'x', $foe->Id);

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'step 1 rejects own character' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01055, 'x', $pal->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 1 rejects opposing character at a different location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01055, 'x', $far->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            'step 2 moves target to adjacent location with RangedAbilityPlayed and ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01055_2,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'target moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                // WHY: moved by an ability, not a duel — target must not engage.
                Assert::false($moves[0]->engage, 'not engaged');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');

                $ranged = $world->theah->queuedOfType(EventRangedAbilityPlayed::class);
                Assert::count(1, $ranged, 'ranged');
                Assert::same($performer->Id, $ranged[0]->performerId, 'performer');
                Assert::same($foe->Id, $ranged[0]->targetId, 'target');
                Assert::same(Game::LOCATION_CITY_FORUM, $ranged[0]->targetLocation, 'location');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'step 2 rejects a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01055_2,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}