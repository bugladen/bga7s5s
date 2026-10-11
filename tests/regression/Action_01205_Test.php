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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01205;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01205;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01205_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01205';
    }

    /**
     * En garde Giacinto + opposing character at Docks.
     *
     * @return array{0:_01205,1:Action_01205,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $giacinto = $world->placeCharacter(new _01205(), Game::LOCATION_CITY_DOCKS, 1);
        $giacinto->Engaged = false;
        $foe = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01205 $action */
        $action = $giacinto->getActions()[0];
        return [$giacinto, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_01205();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
            },

            'available when en garde in the city facing an opposing character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Giacinto is engaged' => function () {
                $world = new TestWorld();
                [$giacinto, $action] = $this->scene($world);
                $giacinto->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when no opposing character shares the location' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposition');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [$giacinto, $action] = $this->scene($world);
                $giacinto->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when not controlled' => function () {
                $world = new TestWorld();
                [$giacinto, $action] = $this->scene($world);
                $giacinto->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'trigger queues transition 01205' => function () {
                $world = new TestWorld();
                [$giacinto, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01205', $transitions[0]->transition, 'name');
                Assert::same($giacinto->Id, $transitions[0]->sourceId, 'source');
            },

            'step 1 args list opposing characters at Giacinto\'s location' => function () {
                $world = new TestWorld();
                [$giacinto, $action, $foe] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01205,
                    'highDramaPlayerTurn_01205'
                );

                Assert::same($giacinto->Id, $args['characterId'], 'giacinto');
                Assert::same([$foe->Id], $args['targetCharacterIds'], 'only victim');
                Assert::false(in_array($far->Id, $args['targetCharacterIds'], true), 'far');
                Assert::false(in_array($ally->Id, $args['targetCharacterIds'], true), 'ally');
            },

            'step 2 args list adjacent city locations' => function () {
                $world = new TestWorld();
                [$giacinto, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01205_2,
                    'highDramaPlayerTurn_01205_2'
                );

                Assert::same($giacinto->Id, $args['characterId'], 'giacinto');
                Assert::same($foe->Id, $args['victimId'], 'victim');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locations'], 'adjacent from Docks');
            },

            'victim choice stores CHOSEN_CARD and goes to victimChosen' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01205,
                    'highDramaPlayerTurn_01205',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['victimChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'victim choice refuses own character and other locations' => function () {
                $world = new TestWorld();
                [$giacinto, $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);

                foreach ([$giacinto, $far] as $bad) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId(
                            $world->game,
                            States::HIGH_DRAMA_PLAYER_TURN_01205,
                            'x',
                            $bad->Id
                        );
                    } catch (UserException | \BgaUserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, $bad->Name);
                }
            },

            // WHY (journal 2026-09-17-07): location commit pays cost engage (no batchId) then
            // ONLY CharacterTargeted with batchId so UL can cancel effects without refunding cost.
            'location choice queues cost engage then batched CharacterTargeted only' => function () {
                $world = new TestWorld();
                [$giacinto, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01205_2,
                    'highDramaPlayerTurn_01205_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $world->game->globals->get(Game::CHOSEN_LOCATION), 'location');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'cost engage only');
                Assert::same($giacinto->Id, $engages[0]->cardId, 'Giacinto');
                Assert::true($engages[0]->batchId === null || $engages[0]->batchId === 0, 'cost unbatched');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'victim');
                Assert::same($action->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no moves yet');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            // WHY: surviving CharacterTargeted emits victim engage + both moves (engage=false) + resolve.
            'surviving CharacterTargeted queues victim engage then both moves then ActionResolved' => function () {
                $world = new TestWorld();
                [$giacinto, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $giacinto->Id;
                $targeted->batchId = 55;
                $targeted->canceled = false;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'victim engage');
                Assert::same($foe->Id, $engages[0]->cardId, 'victim');
                Assert::same(55, $engages[0]->batchId, 'batched');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(2, $moves, 'both move');
                Assert::same($giacinto->Id, $moves[0]->cardId, 'Giacinto first');
                Assert::same($foe->Id, $moves[1]->cardId, 'victim second');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'Forum');
                Assert::false($moves[0]->engage, 'move does not re-engage');
                Assert::same(55, $moves[0]->batchId, 'shared batch');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'canceled CharacterTargeted queues no kidnap effects' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_FORUM);

                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $action->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $action->handleEvent($targeted);

                Assert::count(0, $world->theah->queuedEvents, 'UL cancel holds effects');
            },

            'location choice refuses a non-adjacent location' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01205_2,
                        'x',
                        [Game::LOCATION_CITY_BAZAAR]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
