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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01151;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

final class TestCityCard_01151 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'City Stand-in';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

class Card_01151_Test extends TestCase
{
    public function name(): string
    {
        return '_01151 Shifting Tides';
    }

    /** @return _01151 */
    private function revealed(TestWorld $world): _01151
    {
        /** @var _01151 $scheme */
        $scheme = $world->placeCard(new _01151(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    /** Seed five top-of-city-deck rows so resolve can add one per city location. */
    private function seedCityDeck(TestWorld $world): array
    {
        $tops = [];
        for ($i = 0; $i < 5; $i++) {
            $card = $world->placeCard(new TestCityCard_01151(), Game::LOCATION_CITY_DECK, 0);
            $card->ControllerId = 0;
            $tops[] = $card;
            $world->game->topCityCards[] = ['id' => $card->Id];
        }
        return $tops;
    }

    public function tests(): array
    {
        return [
            'constructs Nature Scheme with empty locations' => function () {
                $scheme = new _01151();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(1, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Nature'), 'Nature');
                Assert::true($scheme->hasWhenRevealedEffect(), 'when revealed');
                Assert::same([], $scheme->locations, 'empty locations');
            },

            'When Revealed discards uncontrolled city cards at city locations' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $city = $world->placeCard(new TestCityCard_01151(), Game::LOCATION_CITY_DOCKS, 0);
                $city->ControllerId = 0;
                $controlled = $world->placeCard(new TestCityCard_01151(), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventCardWhenRevealedEffect();
                $event->cardId = $scheme->Id;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'one uncontrolled');
                Assert::same($city->Id, $discards[0]->cardId, 'docks card');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::false(
                    in_array($controlled->Id, array_map(fn($e) => $e->cardId, $discards), true),
                    'controlled kept'
                );
            },

            // WHY (journal 2026-09-28-01): two-pass priorities so Blood in the Water Forced
            // Renown is wiped — MEDIUM city adds, LOW removeAll, LOWEST player pick.
            'resolving queues MEDIUM city adds, LOW removeAll clears, LOWEST player transition' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $this->seedCityDeck($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $adds = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(5, $adds, 'one per city location');
                foreach ($adds as $add) {
                    Assert::same(Event::MEDIUM_PRIORITY, $add->priority, 'add medium');
                }

                $removes = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(5, $removes, 'clear each location');
                foreach ($removes as $remove) {
                    Assert::true($remove->removeAll, 'removeAll');
                    Assert::same(Event::LOW_PRIORITY, $remove->priority, 'clear low');
                }

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'player pick');
                Assert::same('01151', $transitions[0]->transition, 'name');
                Assert::same(Event::LOWEST_PRIORITY, $transitions[0]->priority, 'lowest');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'choosing a location queues HIGHEST Renown and opponent 01151_2 transitions' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01151,
                    'planningPhaseResolveSchemes_01151',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::same([Game::LOCATION_CITY_DOCKS], $scheme->locations, 'recorded');
                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'one');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');
                Assert::same(Event::HIGHEST_PRIORITY, $renown[0]->priority, 'highest');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one opponent');
                Assert::same('01151_2', $transitions[0]->transition, 'step 2');
                Assert::same(2, $transitions[0]->playerId, 'opponent');
                Assert::same(Event::HIGH_PRIORITY, $transitions[0]->priority, 'high');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'args for step 2 omit already-chosen locations' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->locations = [Game::LOCATION_CITY_DOCKS];

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2,
                    'planningPhaseResolveSchemes_01151_2',
                    ''
                );

                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'docks omitted');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'forum remains');
            },

            'rejects a location that was already chosen' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->locations = [Game::LOCATION_CITY_DOCKS];

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2,
                        'planningPhaseResolveSchemes_01151_2',
                        '',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'already chosen');
            },

            'Planning End clears locations list' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->locations = [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM];

                $dusk = new EventPhasePlanningEnd();
                $world->fireOn($scheme, $dusk);

                Assert::same([], $scheme->locations, 'cleared');
                Assert::true($scheme->IsUpdated, 'dirty');
            },

            'state constants registered' => function () {
                Assert::same(2601151, States::PLANNING_PHASE_RESOLVE_SCHEMES_01151, 'step 1');
                Assert::same(26011512, States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2, 'step 2');
            },
        ];
    }
}
