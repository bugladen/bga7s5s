<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Unattached city attachment stand-in for Let's Haggle reveal / discount tests. */
final class TestAttachment_01147 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test Attachment';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->WealthCost = 2;
        $this->resetCard();
    }
}

class Card_01147_Test extends TestCase
{
    public function name(): string
    {
        return '_01147 Let\'s Haggle';
    }

    public function tests(): array
    {
        return [
            'constructs Bargain Market Scheme with Action_01147' => function () {
                $scheme = new _01147();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(77, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Bargain'), 'Bargain');
                Assert::true($scheme->hasTrait('Market'), 'Market');
                Assert::instanceOf(Action_01147::class, $scheme->getActions()[0], 'Action_01147');
            },

            'resolving adds Renown, queues Bazaar add, and medium-priority 01147 transition' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                $attachment = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_DECK, 0);
                $world->game->cityDeckRevealResult = $attachment;

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two renown');
                $locations = array_map(fn($e) => $e->location, $renown);
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $locations, true), 'forum');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $locations, true), 'bazaar');

                $adds = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(1, $adds, 'add');
                Assert::same($attachment->Id, $adds[0]->cardId, 'attachment');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $adds[0]->location, 'to bazaar');
                Assert::same($attachment->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01147', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'resolving with no attachment clears CHOSEN_CARD and still transitions' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                $world->game->cityDeckRevealResult = null;
                $world->game->globals->set(Game::CHOSEN_CARD, 999);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD), 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'no add');
                Assert::same('01147', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'still transitions');
            },

            // WHY: stateFromCard sinks every revealed id except CHOSEN_CARD via insertCardOnExtremePosition
            // (bottom of city deck). FakeGame records inserts on deckInserts.
            'stateFromCard sinks revealed non-attachment cards' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                $kept = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_DECK, 0);
                $sunk = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_DECK, 0);
                $world->game->globals->set(Game::CHOSEN_CARD, $kept->Id);
                $world->game->globals->set(Game::REVEALED_CARDS, json_encode([$kept->Id, $sunk->Id]));

                $scheme->stateFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01147,
                    'planningPhaseResolveSchemes_01147',
                    ''
                );

                Assert::same(
                    [['id' => $sunk->Id, 'deck' => Game::LOCATION_CITY_DECK, 'onTop' => false]],
                    $world->game->deckInserts,
                    'only sunk'
                );
            },

            'argsFromCard lists revealed card property arrays' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                $card = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_DECK, 0);
                $world->game->globals->set(Game::REVEALED_CARDS, json_encode([$card->Id]));

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01147,
                    'planningPhaseResolveSchemes_01147',
                    ''
                );

                Assert::same($scheme->Id, $args['letsHaggleId'], 'scheme id');
                Assert::count(1, $args['cards'], 'one card');
                Assert::same($card->Id, $args['cards'][0]['id'], 'id');
            },

            'getEquipDiscount is +1 when performer is at Bazaar under this action' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01147 $action */
                $action = $scheme->getActions()[0];
                $performer = $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_BAZAAR, 1);
                $attachment = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_BAZAAR, 0);
                $world->game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $explanations = [];
                $discount = $scheme->getEquipDiscount($world->theah, $performer, $attachment, $explanations);
                Assert::same(1, $discount, 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'getEquipDiscount is 0 when performer is not at Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Action_01147 $action */
                $action = $scheme->getActions()[0];
                $performer = $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_DOCKS, 1);
                $attachment = $world->placeCard(new TestAttachment_01147(), Game::LOCATION_CITY_BAZAAR, 0);
                $world->game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $explanations = [];
                Assert::same(0, $scheme->getEquipDiscount($world->theah, $performer, $attachment, $explanations), 'no discount');
            },

            'state constants registered' => function () {
                Assert::same(2601147, States::PLANNING_PHASE_RESOLVE_SCHEMES_01147, 'scheme');
                Assert::same(401147, States::HIGH_DRAMA_PLAYER_TURN_01147, 'action');
            },
        ];
    }
}
