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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02036a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02036a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02036a';
    }

    /**
     * @return array{0:_02036,1:Action_02036a,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _02036(), Game::LOCATION_PLAYER_HOME, 1);
        $pirate = $world->placeCharacter(
            new GenericCharacter('Pirate', ['Pirate']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_02036a $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $pirate, $foe];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction that targets characters' => function () {
                $action = new Action_02036a();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'state constants registered' => function () {
                Assert::same(402036, States::HIGH_DRAMA_PLAYER_TURN_02036, 'pick');
                Assert::same(4020362, States::HIGH_DRAMA_PLAYER_TURN_02036_2, 'respond');
            },

            'available to a Pirate standing with a non-Pirate opponent' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one');
                Assert::same($pirate->Id, $performers[0]->Id, 'pirate');
            },

            'unavailable when the only opponent is a Pirate or the performer is not' => function () {
                $world = new TestWorld();
                [$scheme, $action, $pirate, $foe] = $this->scene($world);
                $foe->ModifiedTraits = ['Pirate'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'pirate foe');

                $foe->ModifiedTraits = [];
                $pirate->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not pirate');

                $pirate->ModifiedTraits = ['Pirate'];
                $scheme->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not home');
            },

            'rejects your own character, a Pirate, and someone at another location' => function () {
                $world = new TestWorld();
                [, $action, $pirate] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $pirateFoe = $world->placeCharacter(
                    new GenericCharacter('Pirate Foe', ['Pirate']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $away = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 2);

                foreach ([$ally, $pirateFoe, $away] as $illegal) {
                    [$ok] = $action->isValidTargetForAbility($world->game, $illegal);
                    Assert::false($ok, $illegal->Name);
                }
            },

            'EventActionTriggered queues the target chooser' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02036', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'args list non-Pirate opponents at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $pirate, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                $world->placeCharacter(new GenericCharacter('Pirate Foe', ['Pirate']), Game::LOCATION_CITY_DOCKS, 2);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02036, 'highDramaPlayerTurn_02036');
                Assert::same([$foe->Id], $args['ids'], 'only non-pirate');
                Assert::same($pirate->Id, $args['performerId'], 'performer');
            },

            'choosing a target offers their controller a discard' => function () {
                $world = new TestWorld();
                [$scheme, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $world->game->globals->get(Game::CHOSEN_PERFORMER, 0));
                $performer = $action->getPerformersForAction(1, $world->theah)[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02036,
                    'highDramaPlayerTurn_02036',
                    $foe->Id
                );

                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02036_2', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'their turn');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'discarding a hand card avoids the move Home' => function () {
                $world = new TestWorld();
                [$scheme, $action, , $foe] = $this->scene($world);
                $card = $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->activePlayerId = 2;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02036_2,
                    'highDramaPlayerTurn_02036_2',
                    $card->Id
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($card->Id, $discards[0]->cardId, 'card');
                Assert::same(2, $discards[0]->ownerId, 'owner');
                Assert::same($scheme->Id, $discards[0]->sourceId, 'scheme');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::same(1, $resolved[0]->playerId, 'scheme owner');
                Assert::same([''], $world->game->gamestate->transitions, 'empty');
            },

            // Pass moves the target Home, not engaged.
            'passing moves the target Home without engaging' => function () {
                $world = new TestWorld();
                [$scheme, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->activePlayerId = 2;

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_02036_2, 'highDramaPlayerTurn_02036_2');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($foe->Id, $moves[0]->cardId, 'target');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'home');
                Assert::false($moves[0]->engage, 'not engaged');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([''], $world->game->gamestate->transitions, 'empty');
            },

            'rejects a discard that is not in the responder hand' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->activePlayerId = 2;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02036_2,
                        'highDramaPlayerTurn_02036_2',
                        999
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            'rejects a response from the wrong player' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $card = $world->placeCard(new _02038(), Game::LOCATION_HAND, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->activePlayerId = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02036_2,
                        'highDramaPlayerTurn_02036_2',
                        $card->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not your response');
            },
        ];
    }
}
