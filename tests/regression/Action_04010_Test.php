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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04010;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04010_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04010';
    }

    public function tests(): array
    {
        return [
            'is a sorcerer risk action' => function () {
                $action = new Action_04010();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Every discard pile is listed, including empty ones and City Discard.
            // This risk is already in the owner's discard and cannot be one of the two sunk cards.
            // Pass still draws and sinks this card. City Discard sinks to the city deck bottom.
            'lists every pile, then draws and sinks this card' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04010(), Game::LOCATION_HAND, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04010 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'sorcerer');
                $sorcerer->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not a sorcerer');
                $sorcerer->ModifiedTraits = ['Sorcerer'];

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04010', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'always the pile chooser');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010, '');
                Assert::same(Game::LOCATION_CITY_DISCARD, $args['piles'][count($args['piles']) - 1]['location'], 'city discard is last');
                Assert::same('Discard-1', $args['piles'][0]['location'], 'own discard is offered even if empty');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010, '', 0);
                Assert::same('Discard-1', $action->ChosenDiscardPile, 'chosen');
                $risk->Location = 'Discard-1';
                $other = $world->placeCard(new _04006(), 'Discard-1', 2);
                $other->OwnerId = 2;
                $step = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010_2, '');
                $listed = array_map(fn ($card) => $card['id'], $step['cards']);
                Assert::false(in_array($risk->Id, $listed, true), 'this risk is not a sink choice');
                Assert::true(in_array($other->Id, $listed, true), 'the other discard card is');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010_2, '', [$risk->Id]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot sink itself as one of the two');

                $world->theah->takeQueuedEvents();
                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010_2);
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                $sinks = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::same($risk->Id, $sinks[0]->cardId, 'this card sunk');
                Assert::false($sinks[0]->onTop, 'bottom');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $city = $world->placeCard(new _04006(), Game::LOCATION_CITY_DISCARD, 0);
                $action->ChosenDiscardPile = Game::LOCATION_CITY_DISCARD;
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_04010_2, '', [$city->Id]);
                $citySink = $world->theah->queuedOfType(EventCardAddedToCityDeck::class)[0];
                Assert::same($city->Id, $citySink->cardId, 'city deck');
                Assert::false($citySink->onTop, 'city bottom');
            },
        ];
    }
}
