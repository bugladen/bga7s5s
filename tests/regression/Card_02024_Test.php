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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02024;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
class Card_02024_Test extends TestCase
{
    public function name(): string
    {
        return '_02024 Oath of Vengeance';
    }

    /** @return _02024 */
    private function scene(TestWorld $world): _02024
    {
        /** @var _02024 $scheme */
        $scheme = $world->placeCard(new _02024(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Hunt Oathsworn Scheme' => function () {
                $scheme = new _02024();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(64, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Hunt'), 'Hunt');
                Assert::true($scheme->hasTrait('Oathsworn'), 'Oathsworn');
                Assert::true($scheme->hasFaction('Montaigne'), 'Montaigne');
                Assert::same([], $scheme->ReknownLocations, 'locations empty');
            },

            'state constant registered' => function () {
                Assert::same(8002024, States::DUSK_PHASE_BEGIN_02024, 'dusk');
            },

            // WHY: ResolveScheme → +2 Renown to card (EventReknownAddedToCard).
            'resolve queues +2 Renown on card' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $adds = $world->theah->queuedOfType(EventReknownAddedToCard::class);
                Assert::count(1, $adds, 'add');
                Assert::same($scheme->Id, $adds[0]->cardId, 'card');
                Assert::same(2, $adds[0]->amount, 'two');
            },

            // WHY: CharacterDestroyed while scheme at Home + IN_DUEL + your Musketeer
            // participant (not the destroyed one) + Reknown>0 → remove 1 + PlayerGainsReknown.
            'Musketeer destroying adversary collects Renown from scheme' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 2;
                $musketeer = $world->placeCharacter(
                    new GenericCharacter('Musketeer', ['Musketeer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $musketeer;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventCharacterDestroyed();
                $event->characterId = $foe->Id;
                $world->fireOn($scheme, $event);

                Assert::count(1, $world->theah->queuedOfType(EventReknownRemovedFromCard::class), 'removed');
                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'gain');
                Assert::same(1, $gains[0]->playerId, 'player');
                Assert::same(1, $gains[0]->amount, 'one');
            },

            'does not collect when your Musketeer is the one destroyed' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 2;
                $musketeer = $world->placeCharacter(
                    new GenericCharacter('Musketeer', ['Musketeer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $musketeer;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventCharacterDestroyed();
                $event->characterId = $musketeer->Id;
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no gain');
            },

            'does not collect outside a duel or with zero Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 0;
                $musketeer = $world->placeCharacter(
                    new GenericCharacter('Musketeer', ['Musketeer']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $musketeer;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventCharacterDestroyed();
                $event->characterId = $foe->Id;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'zero renown');

                $scheme->Reknown = 2;
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->theah->queuedEvents = [];
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'not in duel');
            },

            // WHY: DuskPhaseBegin with Reknown>0 → one Transition "02024" per renown.
            'DuskPhaseBegin queues one 02024 transition per Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 2;

                $world->fireOn($scheme, new EventDuskPhaseBegin());

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(2, $transitions, 'two');
                Assert::same('02024', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'DuskPhaseBegin with zero Renown queues nothing' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 0;

                $world->fireOn($scheme, new EventDuskPhaseBegin());
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'none');
            },

            // WHY: DuskPhaseEnd clears ReknownLocations.
            'DuskPhaseEnd clears ReknownLocations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ReknownLocations = [Game::LOCATION_CITY_DOCKS];

                $world->fireOn($scheme, new EventDuskPhaseEnd());
                Assert::same([], $scheme->ReknownLocations, 'cleared');
            },

            'actFromCardWithIds moves Renown to a new location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->Reknown = 2;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::DUSK_PHASE_BEGIN_02024,
                    'duskPhaseBegin_02024',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::count(1, $world->theah->queuedOfType(EventReknownRemovedFromCard::class), 'removed');
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'docks');
                Assert::same([Game::LOCATION_CITY_DOCKS], $scheme->ReknownLocations, 'tracked');
            },

            // WHY: sprintf into the message — UserException 2nd arg is int $code, not a format value.
            'refuses duplicate location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ReknownLocations = [Game::LOCATION_CITY_DOCKS];

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::DUSK_PHASE_BEGIN_02024,
                        'duskPhaseBegin_02024',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'duplicate rejected');
            },

            'args exclude already-chosen locations' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ReknownLocations = [Game::LOCATION_CITY_DOCKS];

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::DUSK_PHASE_BEGIN_02024,
                    'duskPhaseBegin_02024',
                    (string) $scheme->Id
                );

                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'forum');
            },
        ];
    }
}
