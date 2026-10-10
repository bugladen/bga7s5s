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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01126;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/**
 * WHY (journal 2026-10-03-01): Leshiye renown/claim locks arm only when
 * Location === ChosenLocation (on-site). ChosenLocation alone is set in step 1
 * while still Home — arming then blocked placement discard / step-2 confirm.
 */
final class TestCityCard_01126 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

class Card_01126_Test extends TestCase
{
    public function name(): string
    {
        return '_01126 Leshiye of the Wood';
    }

    /** @return _01126 */
    private function revealed(TestWorld $world): _01126
    {
        /** @var _01126 $scheme */
        $scheme = $world->placeCard(new _01126(), Game::LOCATION_PLAYER_HOME, 1);
        $world->game->activePlayerId = 1;
        return $scheme;
    }

    /** Place Leshiye on-site so Location === ChosenLocation (locks armed). */
    private function onSite(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): _01126
    {
        $scheme = $this->revealed($world);
        $scheme->ChosenLocation = $location;
        $scheme->Location = $location;
        return $scheme;
    }

    private function renownAdd(TestWorld $world, string $location): EventRenownAddedToLocation
    {
        $event = new EventRenownAddedToLocation();
        $event->location = $location;
        $event->amount = 1;
        $event->playerId = 1;
        $event->description = 'other';
        $event->theah = $world->theah;
        return $event;
    }

    private function renownRemove(TestWorld $world, string $location, string $source = 'other'): EventRenownRemovedFromLocation
    {
        $event = new EventRenownRemovedFromLocation();
        $event->location = $location;
        $event->amount = 1;
        $event->playerId = 1;
        $event->source = $source;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Leshiye Nature Scheme' => function () {
                $scheme = new _01126();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(34, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Ussura'), 'Ussura');
                Assert::true($scheme->hasTrait('Leshiye'), 'Leshiye');
                Assert::true($scheme->hasTrait('Nature'), 'Nature');
                Assert::same('', $scheme->ChosenLocation, 'empty chosen');
            },

            'getPropertyArray exposes chosenLocation' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $props = $scheme->getPropertyArray($world->game);
                Assert::same(Game::LOCATION_CITY_DOCKS, $props['chosenLocation'], 'chosenLocation');
            },

            'resolving queues the 01126 planning transition at medium priority' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01126', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'step-1 args list outermost city locations for 2 players' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                    'planningPhaseResolveSchemes_01126',
                    ''
                );

                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR],
                    $args['locationIds'],
                    'outer'
                );
            },

            'choosing an outermost location stamps ChosenLocation without arming locks' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                    'planningPhaseResolveSchemes_01126',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $scheme->ChosenLocation, 'chosen');
                Assert::same(Game::LOCATION_PLAYER_HOME, $scheme->Location, 'still Home');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->game->globals->get(Game::CHOSEN_LOCATION), 'global');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'next');

                // WHY: locks must stay off while still Home — step-2 renown / placement discard.
                $scheme->eventCheck($this->renownAdd($world, Game::LOCATION_CITY_DOCKS));
                Assert::true(true, 'add not blocked while Home');
            },

            'choosing a non-outer location throws' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                        'x',
                        '',
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'forum refused');
                Assert::same('', $scheme->ChosenLocation, 'unchanged');
            },

            'step-2 args exclude ChosenLocation and on-site Leshiye sites' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;

                $other = $world->placeCard(new _01126(), Game::LOCATION_CITY_FORUM, 2);
                $other->ChosenLocation = Game::LOCATION_CITY_FORUM;

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
                    'planningPhaseResolveSchemes_01126_2',
                    ''
                );

                Assert::same(Game::LOCATION_CITY_DOCKS, $args['chosenLocation'], 'chosen');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'own excluded');
                Assert::false(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'other Leshiye excluded');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'bazaar ok');
                Assert::same(min(2, count($args['locationIds'])), $args['requiredLocationCount'], 'required');
            },

            'step-2 resolve queues renown adds, site renown discard, then SchemeMovedToCity' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
                    'planningPhaseResolveSchemes_01126_2',
                    '',
                    [Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR]
                );

                $adds = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $adds, 'two renown');
                $removes = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removes, 'site discard');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removes[0]->location, 'docks');
                Assert::same(3, $removes[0]->amount, 'all renown');
                Assert::same($scheme->getInjectCode(), $removes[0]->source, 'own source');
                $moves = $world->theah->queuedOfType(EventSchemeMovedToCity::class);
                Assert::count(1, $moves, 'scheme move');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->location, 'to chosen');
                // WHY: remove is queued before SchemeMovedToCity so EventHub has not armed locks yet.
                $queue = $world->theah->queuedEvents;
                $removeIdx = array_search($removes[0], $queue, true);
                $moveIdx = array_search($moves[0], $queue, true);
                Assert::true($removeIdx < $moveIdx, 'discard before move');
                Assert::same(['locationsChosen'], $world->game->gamestate->transitions, 'next');
            },

            'step-2 refuses ChosenLocation and wrong count' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;

                $threwChosen = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
                        'x',
                        '',
                        [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threwChosen = true;
                }
                Assert::true($threwChosen, 'chosen refused');

                $threwCount = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2,
                        'x',
                        '',
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threwCount = true;
                }
                Assert::true($threwCount, 'one of two refused');
            },

            // WHY (journal 2026-10-03-01): ChosenLocation alone must NOT arm locks.
            'eventCheck does not lock while Location is still Home' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;

                $scheme->eventCheck($this->renownAdd($world, Game::LOCATION_CITY_DOCKS));
                $scheme->eventCheck($this->renownRemove($world, Game::LOCATION_CITY_DOCKS));
                $claim = new EventLocationClaimed();
                $claim->location = Game::LOCATION_CITY_DOCKS;
                $claim->theah = $world->theah;
                $scheme->eventCheck($claim);
                Assert::true(true, 'Home: no lock');
            },

            'on-site eventCheck blocks add, foreign remove, and claim at ChosenLocation' => function () {
                $world = new TestWorld();
                $scheme = $this->onSite($world);

                $addThrew = false;
                try {
                    $scheme->eventCheck($this->renownAdd($world, Game::LOCATION_CITY_DOCKS));
                } catch (UserException $e) {
                    $addThrew = true;
                }
                Assert::true($addThrew, 'add blocked');

                $removeThrew = false;
                try {
                    $scheme->eventCheck($this->renownRemove($world, Game::LOCATION_CITY_DOCKS, 'foreign'));
                } catch (UserException $e) {
                    $removeThrew = true;
                }
                Assert::true($removeThrew, 'foreign remove blocked');

                $claim = new EventLocationClaimed();
                $claim->location = Game::LOCATION_CITY_DOCKS;
                $claim->theah = $world->theah;
                $claimThrew = false;
                try {
                    $scheme->eventCheck($claim);
                } catch (UserException $e) {
                    $claimThrew = true;
                }
                Assert::true($claimThrew, 'claim blocked');
            },

            'on-site eventCheck allows own-source renown remove and other locations' => function () {
                $world = new TestWorld();
                $scheme = $this->onSite($world);

                $scheme->eventCheck($this->renownRemove($world, Game::LOCATION_CITY_DOCKS, $scheme->getInjectCode()));
                $scheme->eventCheck($this->renownAdd($world, Game::LOCATION_CITY_FORUM));
                Assert::true(true, 'own source + other site ok');
            },

            'SchemeMovedToCity disables claim, discards city cards, and sends characters Home' => function () {
                $world = new TestWorld();
                $scheme = $this->revealed($world);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                // Simulate EventHub setting Location before the card handler runs.
                $scheme->Location = Game::LOCATION_CITY_DOCKS;

                $city = $world->placeCard(new TestCityCard_01126(), Game::LOCATION_CITY_DOCKS, 0);
                $char = $world->placeCharacter(new GenericCharacter('Local'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventSchemeMovedToCity();
                $event->scheme = $scheme;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                Assert::false(
                    $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed,
                    'claim locked'
                );
                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'city discard');
                Assert::same($city->Id, $discards[0]->cardId, 'city card');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'char home');
                Assert::same($char->Id, $moves[0]->cardId, 'character');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::same(Game::LOCATION_PLAYER_HOME, $char->Location, 'deck move');
            },

            // WHY: dusk phase sends all schemes to The Locker (StatesTrait); card clears its site lock.
            'CardSentToLocker clears ChosenLocation and re-enables claim' => function () {
                $world = new TestWorld();
                $scheme = $this->onSite($world);
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);

                $event = new EventCardSentToLocker();
                $event->cardId = $scheme->Id;
                $world->fireOn($scheme, $event);

                Assert::same('', $scheme->ChosenLocation, 'cleared');
                Assert::true(
                    $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed,
                    'claim restored'
                );
            },

            'state constants registered' => function () {
                Assert::same(26011261, States::PLANNING_PHASE_RESOLVE_SCHEMES_01126, 'step 1');
                Assert::same(26011262, States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2, 'step 2');
            },
        ];
    }
}
