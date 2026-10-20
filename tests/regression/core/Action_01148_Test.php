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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01148;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01148;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01148_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01148';
    }

    /** @return array{0:_01148,1:Action_01148,2:GenericCharacter,3:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01148(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_01148 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer, $merc];
    }

    public function tests(): array
    {
        return [
            'implements IAbilityThatTargetsCharacters' => function () {
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, new Action_01148(), 'targets chars');
            },

            'available with city character opposing a Mercenary' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without an opposing Mercenary' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01148(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Solo'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01148 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no merc');
            },

            'performers are only characters with opposing Mercenaries' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'has merc');
                Assert::false(in_array($lonely->Id, $ids, true), 'lonely excluded');
            },

            'trigger queues transition 01148' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('01148', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'name');
                Assert::same($scheme->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'source');
            },

            'args list opposing Mercenaries at performer location' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $merc] = $this->scene($world);
                $farMerc = $world->placeCharacter(new GenericCharacter('Far', ['Mercenary']), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01148, 'x');

                Assert::same($scheme->Id, $args['schemeId'], 'scheme');
                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::true(in_array($merc->Id, $args['charactersIds'], true), 'local merc');
                Assert::false(in_array($farMerc->Id, $args['charactersIds'], true), 'far merc');
            },

            'choosing a valid Mercenary sets CHOSEN_TARGET' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148,
                    'x',
                    $merc->Id
                );

                Assert::same($merc->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(['mercenaryChosen'], $world->game->gamestate->transitions, 'named');
            },

            'refuses a non-Mercenary target' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01148,
                        'x',
                        $foe->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not merc');
            },

            // WHY: empty hand or uncontrolled target short-circuits with ActionResolved + cancel.
            'stateFromAction cancels when hand is empty' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_2,
                    'x'
                );

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cancel'], $world->game->gamestate->transitions, 'cancel');
            },

            'stateFromAction proceeds when hand has cards' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->placeCard(new _01114(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->stateFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_2,
                    'x'
                );

                Assert::same(['proceed'], $world->game->gamestate->transitions, 'proceed');
            },

            'discarding a hand card queues discard + 01148_3 transition' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $merc] = $this->scene($world);
                $hand = $world->placeCard(new _01114(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_3,
                    'x',
                    $hand->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                Assert::same('01148_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'loop');
                // WHY: production calls bare nextState() (no transition name) — FakeGamestate records null.
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
                unset($scheme);
            },

            'finishing discards (id 0) queues ActionResolved' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_3,
                    'x',
                    0
                );

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'engage choice (id 1) queues EventCardEngaged and loops' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_4,
                    'x',
                    1
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage');
                Assert::same($merc->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'target');
                Assert::same('01148_4', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'loop');
            },

            'wound choice (id 2) queues EventCharacterBeingWounded and loops' => function () {
                $world = new TestWorld();
                [, $action, $performer, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $merc->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01148_4,
                    'x',
                    2
                );

                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wound');
                Assert::same($merc->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'target');
                Assert::same('01148_4', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'loop');
            },
        ];
    }
}
