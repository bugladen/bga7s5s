<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsPlayers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02002_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02002';
    }

    /**
     * @return array{0:_02002,1:Action_02002}
     */
    private function scene(TestWorld $world): array
    {
        $eli = $world->placeCharacter(new _02002(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_02002 $action */
        $action = $eli->getActions()[0];
        return [$eli, $action];
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer CharacterAction that targets players' => function () {
                $action = new Action_02002();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcerer');
                Assert::instanceOf(IAbilityThatTargetsPlayers::class, $action, 'targets players');
            },

            'available in city when owner is Sorcerer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Player Home' => function () {
                $world = new TestWorld();
                [$eli, $action] = $this->scene($world);
                $eli->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when owner loses Sorcerer trait' => function () {
                $world = new TestWorld();
                [$eli, $action] = $this->scene($world);
                $eli->ModifiedTraits = ['Strega', 'Red Hand', 'Vodacce'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Sorcerer');
            },

            'trigger queues transition 02002' => function () {
                $world = new TestWorld();
                [$eli, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02002', $transitions[0]->transition, 'name');
                Assert::same($eli->Id, $transitions[0]->sourceId, 'source');
            },

            'player-choice args list loadPlayersBasicInfos' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02002,
                    'x'
                );
                Assert::count(2, $args['players'], 'two players');
                $ids = array_column($args['players'], 'id');
                Assert::true(in_array(1, $ids, true), 'p1');
                Assert::true(in_array(2, $ids, true), 'p2');
            },

            // WHY: City Action peeks top 3 of target player's faction deck.
            'choosing a player peeks top 3 and starts Sorcerer ability' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $a = new GenericCharacter('Top1');
                $a->setId(501);
                $b = new GenericCharacter('Top2');
                $b->setId(502);
                $c = new GenericCharacter('Top3');
                $c->setId(503);
                $world->game->registerDbCard($a);
                $world->game->registerDbCard($b);
                $world->game->registerDbCard($c);
                $world->game->topFactionCards = [['id' => 501], ['id' => 502], ['id' => 503]];

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02002,
                    'x',
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(3, $cards, 'peek 3');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                Assert::same(['playerChosen'], $world->game->gamestate->transitions, 'next');
            },

            'source peeks getCardsOnTopOfPlayerFactionDeck with count 3' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/tac/actions/Action_02002.php');
                Assert::contains('getCardsOnTopOfPlayerFactionDeck($id, 3)', $src, 'peek top 3');
                Assert::contains('createSorcererAbilityStartEvent', $src, 'Start');
                Assert::contains('createSorcererAbilityPlayedEvent', $src, 'Played');
                Assert::contains('createActionResolvedEvent', $src, 'Resolved');
            },

            'reorder path queues Sorcerer Played and ActionResolved' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([
                    (object)['id' => 601],
                    (object)['id' => 602],
                ]));

                $deck = $world->game->getPlayerFactionDeckName(2);
                $c1 = new GenericCharacter('R1');
                $c1->setId(601);
                $c1->ControllerId = 2;
                $c1->Location = $deck;
                $c2 = new GenericCharacter('R2');
                $c2->setId(602);
                $c2->ControllerId = 2;
                $c2->Location = $deck;
                $world->game->registerDbCard($c1);
                $world->game->registerDbCard($c2);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02002_3,
                    'x',
                    [602, 601]
                );

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(
                    [
                        ['id' => 602, 'deck' => $deck, 'onTop' => true],
                        ['id' => 601, 'deck' => $deck, 'onTop' => true],
                    ],
                    $world->game->deckInserts,
                    'reorder inserts'
                );
            },
        ];
    }
}
