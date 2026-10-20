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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01125;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01125_Test extends TestCase
{
    public function name(): string
    {
        return '_01125 The Boar\'s Guile';
    }

    /** @return array{0:_01125,1:GenericCharacter} */
    private function revealed(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01125(), Game::LOCATION_PLAYER_HOME, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->activePlayerId = 1;
        return [$scheme, $foe];
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Cunning Hunt Scheme' => function () {
                $scheme = new _01125();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(40, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Ussura'), 'Ussura');
                Assert::true($scheme->hasTrait('Cunning'), 'Cunning');
                Assert::true($scheme->hasTrait('Hunt'), 'Hunt');
            },

            'resolving queues the 01125 planning transition at medium priority' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01125', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            // WHY (journal 2026-10-03-13): enemy = isNotControlledByPlayer + in play.
            // Available mercs (ControllerId 0) and locker/discard enemies must not appear —
            // client used to highlight city mercs via null-divId dojo.query fallthrough.
            'state _4 args list only in-play enemy characters' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);
                $homeFoe = $world->placeCharacter(new GenericCharacter('Home Foe'), Game::LOCATION_PLAYER_HOME, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc'), Game::LOCATION_CITY_BAZAAR, 0);
                $lockerFoe = $world->placeCharacter(new GenericCharacter('Locker Foe'), Game::LOCATION_CITY_LOCKER, 2);
                $discardFoe = $world->placeCharacter(
                    new GenericCharacter('Discard Foe'),
                    $world->game->getPlayerDiscardDeckName(2),
                    2
                );

                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
                    'planningPhaseResolveSchemes_01125_4',
                    ''
                );

                Assert::true(in_array($foe->Id, $args['characterIds'], true), 'city foe');
                Assert::true(in_array($homeFoe->Id, $args['characterIds'], true), 'home foe');
                Assert::false(in_array($ally->Id, $args['characterIds'], true), 'ally excluded');
                Assert::false(in_array($merc->Id, $args['characterIds'], true), 'available merc excluded');
                Assert::false(in_array($lockerFoe->Id, $args['characterIds'], true), 'locker foe excluded');
                Assert::false(in_array($discardFoe->Id, $args['characterIds'], true), 'discard foe excluded');
            },

            'choosing an enemy stamps Adversary of Yevgeni' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);

                $scheme->actFromCardWithId(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
                    'planningPhaseResolveSchemes_01125_4',
                    '',
                    $foe->Id
                );

                Assert::true($foe->hasCondition(Game::ADVERSARY_OF_YEVGENI), 'stamped');
                // WHY: nextState("") records '' in FakeGamestate (empty transition key).
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');
            },

            'choosing an available merc throws' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);
                $merc = $world->placeCharacter(new GenericCharacter('Merc'), Game::LOCATION_CITY_BAZAAR, 0);

                $threw = false;
                try {
                    $scheme->actFromCardWithId(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
                        'planningPhaseResolveSchemes_01125_4',
                        '',
                        $merc->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'merc rejected');
            },

            'combat cards gain +1 Thrust when the adversary is Adversary of Yevgeni' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe->addCondition(Game::ADVERSARY_OF_YEVGENI);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $actor->Id;
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $world->fireOn($scheme, $event);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
            },

            'combat cards gain nothing when the adversary is not marked' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelOpponent = $foe;

                $event = new EventDuelCalculateCombatCardStats();
                $event->theah = $world->theah;
                $world->fireOn($scheme, $event);

                Assert::same(0, $event->thrust, 'no bonus');
            },

            // WHY: Dusk Forced — if the chosen character is at Home, gain a Renown, then clear the mark.
            'Dusk with the Adversary at Home grants Renown and clears the condition' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                $foe->addCondition(Game::ADVERSARY_OF_YEVGENI);

                $world->fireOn($scheme, new EventDuskPhaseBegin());

                Assert::count(1, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'Renown');
                Assert::false($foe->hasCondition(Game::ADVERSARY_OF_YEVGENI), 'cleared');
            },

            'Dusk with the Adversary in the city clears the condition without Renown' => function () {
                $world = new TestWorld();
                [$scheme, $foe] = $this->revealed($world);
                $foe->addCondition(Game::ADVERSARY_OF_YEVGENI);

                $world->fireOn($scheme, new EventDuskPhaseBegin());

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no Renown');
                Assert::false($foe->hasCondition(Game::ADVERSARY_OF_YEVGENI), 'cleared');
            },

            'placing Renown on a location transitions to choose the enemy' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01125,
                    'planningPhaseResolveSchemes_01125',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                Assert::count(1, $world->theah->queuedEvents, 'Renown added');
                Assert::same(['reknownPlaced'], $world->game->gamestate->transitions, 'next');
            },
        ];
    }
}
