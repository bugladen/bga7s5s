<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Uncontrolled city-deck stand-in. */
final class TestCityCard_04037 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Corner Cat';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }

    public function canBeDiscardedFromCity(): bool
    {
        return true;
    }
}

class Action_04037_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04037';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde Academic. Discard is the cost (asEffect false) and is peeked
            // before it could reshuffle into the look. Pass adds nothing and still sinks
            // the rest, then locks an extra action to this performer. An empty look
            // skips the picker and still grants the extra action.
            'discards a city card, sinks the look, and locks another action' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04037(), Game::LOCATION_HAND, 1);
                $academic = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $city = $world->placeCard(new TestCityCard_04037(), Game::LOCATION_CITY_DOCKS, 0);
                $peekA = $world->placeCard(new TestCityCard_04037(), Game::LOCATION_CITY_DECK, 0);
                $peekB = $world->placeCard(new TestCityCard_04037(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $peekA->Id], ['id' => $peekB->Id]];
                /** @var Action_04037 $action */
                $action = $risk->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'discardable city card');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04037, '', $city->Id);
                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class)[0];
                Assert::same($city->Id, $discard->cardId, 'discarded');
                Assert::false($discard->asEffect, 'cost, not effect');
                Assert::same('04037_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'look');

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_04037_2);
                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'pass adds none');
                $sunk = array_map(fn($event) => $event->cardId, $world->theah->queuedOfType(EventCardAddedToCityDeck::class));
                Assert::same([$peekA->Id, $peekB->Id], $sunk, 'both sunk');
                Assert::false($world->theah->queuedOfType(EventCardAddedToCityDeck::class)[0]->onTop, 'bottom');
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'extra action');
                Assert::same($academic->Id, $world->game->globals->get(Game::EXTRA_ACTION_PERFORMER), 'same performer');
                Assert::same('pass', end($world->game->gamestate->transitions), 'pass');

                $world->theah->takeQueuedEvents();
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([$peekA->getPropertyArray($world->game)]));
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04037_2, '', $peekA->Id);
                Assert::same($peekA->Id, $world->theah->queuedOfType(EventCityCardAddedToLocation::class)[0]->cardId, 'added');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventCityCardAddedToLocation::class)[0]->location, 'here');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDeck::class), 'the chosen card is not sunk');

                $academic->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged academic');

                $threw = false;
                try {
                    $world->game->globals->set(Game::CHOSEN_PERFORMER, $academic->Id);
                    $world->game->globals->set(Game::CHOSEN_CARD, json_encode([$peekA->getPropertyArray($world->game)]));
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04037_2, '', 99999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in the look');

                $empty = new TestWorld();
                $risk2 = $empty->placeCard(new _04037(), Game::LOCATION_HAND, 1);
                $academic2 = $empty->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $corner = $empty->placeCard(new TestCityCard_04037(), Game::LOCATION_CITY_DOCKS, 0);
                $empty->game->globals->set(Game::CHOSEN_PERFORMER, $academic2->Id);
                /** @var Action_04037 $action2 */
                $action2 = $risk2->getActions()[0];
                $action2->actFromActionWithId($empty->game, States::HIGH_DRAMA_PLAYER_TURN_04037, '', $corner->Id);
                Assert::count(0, $empty->theah->queuedOfType(EventTransition::class), 'empty deck skips the picker');
                Assert::same($academic2->Id, $empty->game->globals->get(Game::EXTRA_ACTION_PERFORMER), 'extra action anyway');
                Assert::count(1, $empty->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
