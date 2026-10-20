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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01176;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01180;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01187;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01180;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01180_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01180';
    }

    /**
     * Controlled Kaj in city.
     *
     * @return array{0:_01180,1:Action_01180}
     */
    private function scene(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        /** @var _01180 $kaj */
        $kaj = $world->placeCharacter(new _01180(), $location, 1);
        /** @var Action_01180 $action */
        $action = $kaj->getActions()[0];
        return [$kaj, $action];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_01180(), 'CharacterAction');
            },

            'available when Kaj is controlled in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Kaj is at Home' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_PLAYER_HOME);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when Kaj is uncontrolled' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $kaj->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            'unavailable to the opposing player' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            'trigger queues transition 01180' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $filler = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $filler->Id]];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01180', $transitions[0]->transition, 'name');
                Assert::same($kaj->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'args for choose state expose the top four city cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $cards = [];
                for ($i = 0; $i < 4; $i++) {
                    $cards[] = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                    $world->game->topCityCards[] = ['id' => $cards[$i]->Id];
                }

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01180_3,
                    'highDramaPlayerTurn_01180_3'
                );

                Assert::count(4, $args['cards'], 'four');
                Assert::same($cards[0]->Id, $args['cards'][0]['id'], 'first');
            },

            'pass sinks all four revealed cards and ActionResolved' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $ids = [];
                for ($i = 0; $i < 4; $i++) {
                    $card = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                    $ids[] = $card->Id;
                    $world->game->topCityCards[] = ['id' => $card->Id];
                }

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_01180_3);

                $sinks = $world->theah->queuedOfType(EventCardAddedToCityDeck::class);
                Assert::count(4, $sinks, 'four sinks');
                foreach ($sinks as $i => $sink) {
                    Assert::same($ids[$i], $sink->cardId, "sink {$i}");
                    Assert::false($sink->onTop, 'bottom/sink');
                }
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($kaj->ControllerId, $world->theah->queuedOfType(EventActionResolved::class)[0]->playerId, 'controller');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            // WHY (journal 2026-04-08-07 bug 2): must be Attachment AND Artifact (|| not &&).
            'choosing a non-Artifact from the top 4 is refused' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $gun = $world->placeCard(new _01049(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $gun->Id]];

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01180_3,
                        'x',
                        $gun->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::same([], $world->game->gamestate->transitions, 'no advance');
            },

            'choosing an id outside the top 4 is refused' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $in = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $out = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $in->Id]];

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01180_3,
                        'x',
                        $out->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'outside');
            },

            'choosing an Artifact advances to cardChosen and stores CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $artifact->Id]];

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01180_3,
                    'highDramaPlayerTurn_01180_3',
                    $artifact->Id
                );

                Assert::same($artifact->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'cardChosen');
            },

            'args for performer state list only controller characters at Kaj\'s location' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $artifact->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01180_4,
                    'highDramaPlayerTurn_01180_4'
                );

                Assert::true(in_array($kaj->Id, $args['ids'], true), 'Kaj');
                Assert::true(in_array($ally->Id, $args['ids'], true), 'ally');
                Assert::false(in_array($foe->Id, $args['ids'], true), 'foe');
                Assert::false(in_array($far->Id, $args['ids'], true), 'far');
                Assert::same($artifact->Id, $args['chosenCard']['id'], 'chosen');
            },

            // WHY (journal 2026-04-08-07 bug 1): affordability is WealthCost - discount vs handWealth.
            'choosing a performer queues EnteringPay and 01180_4 transition' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $artifact->Id);
                $world->game->activePlayerId = 1;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01180_4,
                    'highDramaPlayerTurn_01180_4',
                    $kaj->Id
                );

                Assert::same($kaj->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay');
                Assert::same($artifact->Id, $pay[0]->cardId, 'attachment');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $pay[0]->payStateType, 'equip pay');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01180_4', $transitions[0]->transition, 'name');
                Assert::same(['performerChosen'], $world->game->gamestate->transitions, 'performerChosen');
            },

            'choosing a character at another location is refused' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $artifact->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01180_4,
                        'x',
                        $far->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'far refused');
                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },

            'paying sinks the other revealed cards, equips, and ActionResolved' => function () {
                $world = new TestWorld();
                [$kaj, $action] = $this->scene($world);
                $artifact = $world->placeCard(new _01187(), Game::LOCATION_CITY_DECK, 0);
                $other = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [
                    ['id' => $artifact->Id],
                    ['id' => $other->Id],
                ];
                $world->game->globals->set(Game::CHOSEN_CARD, $artifact->Id);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $kaj->Id);
                // WHY: pay state normally stamps these; isolated act test sets them directly.
                $world->game->globals->set(Game::DISCOUNT, 1);
                $world->game->globals->set(Game::DISCOUNT_EXPLAINATIONS, 'Kaj');
                $world->game->activePlayerId = 1;

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01180_5,
                    'highDramaPlayerTurn_01180_5',
                    []
                );

                $sinks = $world->theah->queuedOfType(EventCardAddedToCityDeck::class);
                Assert::count(1, $sinks, 'sink other');
                Assert::same($other->Id, $sinks[0]->cardId, 'other');
                Assert::false($sinks[0]->onTop, 'sink');

                // WHY: EventFactory equip helper returns EventAttachmentEquipping —
                // Equipped fires later from EventHub (same pin as Reaction_01155).
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'equip');
                Assert::same($artifact->Id, $equip[0]->attachmentId, 'artifact');
                Assert::same($kaj->Id, $equip[0]->characterId, 'host');
                Assert::true($equip[0]->asAction, 'as action');
                Assert::same($action->Id, $equip[0]->abilityId, 'ability');

                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'free equip');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['artifactEquipped'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
