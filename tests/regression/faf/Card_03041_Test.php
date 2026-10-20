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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03041;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03041_Test extends TestCase
{
    public function name(): string
    {
        return '_03041 Proper Study';
    }

    public function tests(): array
    {
        return [
            'constructs a Castille scholarship scheme' => function () {
                $scheme = new _03041();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(68, $scheme->Initiative, 'initiative');
                Assert::same(1, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Alquimia'), 'Alquimia');
                Assert::true($scheme->hasTrait('Scholarship'), 'Scholarship');
                Assert::instanceOf(Reaction_03041::class, $scheme->getReactions()[0], 'reaction');
            },

            'resolve adds Renown to the Docks and the Grand Bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03041(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->theah = $world->theah;
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $scheme->handleEvent($resolve);
                $locations = array_map(
                    fn ($event) => $event->location,
                    $world->theah->queuedOfType(EventRenownAddedToLocation::class)
                );
                Assert::same([Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR], $locations, 'docks and bazaar');
            },

            // WHY: Chosen schemes sit at Home until Dusk. Draw two, or three with an
            // Academic, clamped to what the deck and discard can actually supply.
            'end of Planning draws, then requires an equal discard' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03041(), Game::LOCATION_PLAYER_HOME, 1);
                $world->game->deckLocationCounts['Deck-1'] = 5;
                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $scheme->handleEvent($end);
                Assert::count(2, $world->theah->queuedOfType(EventCardDrawn::class), 'two');
                Assert::same(2, $scheme->cardsToDiscard, 'discard two');
                Assert::same('03041', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'discard step');

                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->takeQueuedEvents();
                $scheme->handleEvent($end);
                Assert::count(3, $world->theah->queuedOfType(EventCardDrawn::class), 'three with an Academic');
                Assert::same(3, $scheme->cardsToDiscard, 'discard three');

                $world->game->deckLocationCounts['Deck-1'] = 1;
                $world->theah->takeQueuedEvents();
                $scheme->handleEvent($end);
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'clamped to the deck');
                Assert::same(1, $scheme->cardsToDiscard, 'discard what was drawn');

                $hand = $world->placeCard(new _03036(), Game::LOCATION_HAND, 1);
                $args = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_END_03041, 'x', '');
                Assert::same(1, $args['cardsToDiscard'], 'clamped to the hand');
                $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_END_03041, 'x', '', [$hand->Id]);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::same(0, $scheme->cardsToDiscard, 'cleared');
                Assert::same([''], array_slice($world->game->gamestate->transitions, -1), 'sole empty transition');

                $scheme->cardsToDiscard = 2;
                $world->placeCard(new _03036(), Game::LOCATION_HAND, 1);
                $threw = false;
                try {
                    $scheme->actFromCardWithIds($world->game, States::PLANNING_PHASE_END_03041, 'x', '', [$hand->Id]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must discard the whole count');
            },

            'a scheme that is not at Home, or a deck with nothing left, does not draw' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03041(), Game::LOCATION_CITY_LOCKER, 1);
                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $scheme->handleEvent($end);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'not home');

                $scheme->Location = Game::LOCATION_PLAYER_HOME;
                $scheme->handleEvent($end);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'nothing to draw');
                Assert::same(0, $scheme->cardsToDiscard, 'count stays zero');
            },
        ];
    }
}
