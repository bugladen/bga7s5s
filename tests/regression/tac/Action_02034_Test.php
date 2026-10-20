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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02034_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02034';
    }

    /**
     * @return array{0:_02034,1:Action_02034,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $combat = 2, bool $engaged = false): array
    {
        $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->ModifiedCombat = $combat;
        $foe->Engaged = $engaged;
        /** @var Action_02034 $action */
        $action = $torvo->getActions()[0];
        return [$torvo, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_02034();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            'state constants registered' => function () {
                Assert::same(402034, States::HIGH_DRAMA_PLAYER_TURN_02034, 'pick');
                Assert::same(4020342, States::HIGH_DRAMA_PLAYER_TURN_02034_2, 'offer');
            },

            // WHY: Printed gate is 2 Combat or more. Engaged targets stay legal (decline still draws).
            'available against an opposing character with 2 Combat, even if engaged' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 2, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged ok');
            },

            'unavailable below 2 Combat, off location, or when Torvo is not in the city' => function () {
                $world = new TestWorld();
                [$torvo, $action, $foe] = $this->scene($world, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'low combat');

                $foe->ModifiedCombat = 3;
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'other location');

                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $torvo->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
            },

            'does not target your own character' => function () {
                $world = new TestWorld();
                $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $ally->ModifiedCombat = 5;
                /** @var Action_02034 $action */
                $action = $torvo->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ally');
                [$ok] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ok, 'invalid');
            },

            'EventActionTriggered queues the target chooser' => function () {
                $world = new TestWorld();
                [$torvo, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('02034', $transitions[0]->transition, 'name');
                Assert::same($torvo->Id, $transitions[0]->sourceId, 'source');
            },

            'args list qualifying opponent ids' => function () {
                $world = new TestWorld();
                [$torvo, $action, $foe] = $this->scene($world);
                $weak = $world->placeCharacter(new GenericCharacter('Weak'), Game::LOCATION_CITY_DOCKS, 2);
                $weak->ModifiedCombat = 1;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02034, 'highDramaPlayerTurn_02034');
                Assert::same($torvo->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'only 2 combat');
            },

            'choosing a target stores them and offers the challenge' => function () {
                $world = new TestWorld();
                [$torvo, $action, $foe] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02034,
                    'highDramaPlayerTurn_02034',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('02034_2', $transitions[0]->transition, 'name');
                Assert::same($foe->ControllerId, $transitions[0]->playerId, 'their turn');
                Assert::same($torvo->Id, $transitions[0]->sourceId, 'source');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'rejects an illegal target' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02034,
                        'highDramaPlayerTurn_02034',
                        $foe->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            // Decline (id 2): Torvo draws and the action resolves. No challenge globals.
            'declining the challenge draws a card for Torvo' => function () {
                $world = new TestWorld();
                [$torvo, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02034_2,
                    'highDramaPlayerTurn_02034_2',
                    2
                );

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same($torvo->ControllerId, $draws[0]->playerId, 'torvo player');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');
                Assert::same(0, (int) $world->game->globals->get(Game::CHALLENGE_TYPE, 0), 'no challenge');
            },

            // Accept (id 1): Combat challenge of type TORVO_ESPADA. Challenger is the target, defender is Torvo.
            'accepting sets a Torvo Espada Combat challenge' => function () {
                $world = new TestWorld();
                [$torvo, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02034_2,
                    'highDramaPlayerTurn_02034_2',
                    1
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'challenger');
                Assert::same($torvo->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'defender');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same(Game::TORVO_ESPADA_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type 15');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02034_3', $transitions[0]->transition, 'name');
                Assert::same($foe->ControllerId, $transitions[0]->playerId, 'their turn');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');
            },

            'rejects an id other than accept or decline' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $foe->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02034_2,
                        'highDramaPlayerTurn_02034_2',
                        3
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
