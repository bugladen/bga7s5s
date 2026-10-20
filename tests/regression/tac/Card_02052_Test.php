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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02052;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02052_Test extends TestCase
{
    public function name(): string
    {
        return '_02052 Gutter Full of Roses';
    }

    /** @return _02052 */
    private function scene(TestWorld $world): _02052
    {
        /** @var _02052 $scheme */
        $scheme = $world->placeCard(new _02052(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Riot Scheme' => function () {
                $scheme = new _02052();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(9, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Neutral'), 'Neutral');
                Assert::true($scheme->hasTrait('Riot'), 'Riot');
                Assert::true($scheme->hasTrait('Brawl'), 'Brawl');
                Assert::same(2602052, States::PLANNING_PHASE_RESOLVE_SCHEMES_02052, 'state');
            },

            'resolve adds a Renown to the Bazaar and then asks for a move' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $added[0]->location, 'bazaar');
                Assert::same(1, $added[0]->amount, 'one');
                Assert::false($added[0]->isMove, 'printed add is not a move');
                Assert::same($scheme->getInjectCode(), $added[0]->description, 'source');
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02052', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'ignores resolve of a different scheme' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $other = $world->placeCard(new _02052(), Game::LOCATION_PLAYER_HOME, 2);
                $event = new EventResolveScheme();
                $event->scheme = $other;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'args are city locations that have Renown, except the Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 4);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                $args = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_02052, 'planningPhaseResolveSchemes_02052', (string) $scheme->Id);
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'forum only');
            },

            'moving Renown checks the batch, marks the add as a move, and uses the empty transition' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02052,
                    'planningPhaseResolveSchemes_02052',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_FORUM]
                );

                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class);
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_FORUM, $moving[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moving[0]->toLocation, 'to');
                Assert::same(1, $moving[0]->amount, 'one');
                Assert::same($moving[0]->batchId, $removed[0]->batchId, 'remove batch');
                Assert::same($moving[0]->batchId, $added[0]->batchId, 'add batch');
                Assert::true($added[0]->isMove, 'move');
                Assert::same([''], $world->game->gamestate->transitions, 'named empty transition');
            },

            'cannot move the Bazaar onto itself or an empty location' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                foreach ([Game::LOCATION_CITY_BAZAAR, Game::LOCATION_CITY_DOCKS] as $location) {
                    $threw = false;
                    try {
                        $scheme->actFromCardWithIds(
                            $world->game,
                            States::PLANNING_PHASE_RESOLVE_SCHEMES_02052,
                            'planningPhaseResolveSchemes_02052',
                            (string) $scheme->Id,
                            [$location]
                        );
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, $location);
                }
                Assert::count(0, $world->theah->queuedEvents, 'none queued');
            },

            'an unknown location is rejected by the city map before the card check' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02052,
                        'planningPhaseResolveSchemes_02052',
                        (string) $scheme->Id,
                        ['Nowhere']
                    );
                } catch (\Exception $e) {
                    $threw = !($e instanceof UserException);
                }
                Assert::true($threw, 'map exception');
            },

            // WHY: The state has only the empty transition, so a legal pass uses bare nextState().
            // A location that still has Renown is not optional.
            'pass is legal only when no other location has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 3);
                $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_02052, 'planningPhaseResolveSchemes_02052', (string) $scheme->Id);
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_02052, 'planningPhaseResolveSchemes_02052', (string) $scheme->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must move');
            },

            // WHY: "At the Bazaar" is the challenger's location, not the defender's.
            // The collector is the controller of the surviving participant.
            'home scheme collects Bazaar Renown when a duel adversary is destroyed' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_BAZAAR, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $challenger;
                $world->theah->duelOpponent = $defender;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);

                $event = new EventCharacterDestroyed();
                $event->characterId = $defender->Id;
                $world->fireOn($scheme, $event);

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                $gained = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::same(2, $removed[0]->playerId, 'challenger controller collects');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $removed[0]->location, 'bazaar');
                Assert::same(1, $removed[0]->amount, 'one');
                Assert::same(2, $gained[0]->playerId, 'gain');
                Assert::same(1, $gained[0]->amount, 'amount');
            },

            'destroying the challenger pays the defender controller' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_BAZAAR, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_BAZAAR, 1);
                $world->theah->duelActor = $challenger;
                $world->theah->duelOpponent = $defender;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 2);

                $event = new EventCharacterDestroyed();
                $event->characterId = $challenger->Id;
                $world->fireOn($scheme, $event);

                Assert::same(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class)[0]->playerId, 'defender controller');
            },

            'does not collect outside a home scheme, a bazaar duel, a participant, or an empty bazaar' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_BAZAAR, 2);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_BAZAAR, 1);
                $world->theah->duelActor = $challenger;
                $world->theah->duelOpponent = $defender;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 1);

                $event = new EventCharacterDestroyed();
                $event->characterId = $defender->Id;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'challenger is not at the bazaar');

                $challenger->Location = Game::LOCATION_CITY_BAZAAR;
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'not a duel');

                $world->game->globals->set(Game::IN_DUEL, true);
                $event->characterId = $bystander->Id;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'not a participant');

                $event->characterId = $defender->Id;
                $scheme->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'scheme left home');

                $scheme->Location = Game::LOCATION_PLAYER_HOME;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 0);
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'empty bazaar');
            },
        ];
    }
}
