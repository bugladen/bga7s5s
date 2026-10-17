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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03005_Test extends TestCase
{
    public function name(): string
    {
        return '_03005 No Mercy';
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce scheme with a Reaction' => function () {
                $scheme = new _03005();
                Assert::instanceOf(Scheme::class, $scheme, 'scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(91, $scheme->Initiative, 'initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($scheme->hasTrait('Villainous'), 'Villainous');
                Assert::true($scheme->hasTrait('Duress'), 'Duress');
                Assert::instanceOf(Reaction_03005::class, $scheme->getReactions()[0], 'reaction');
            },

            'resolve adds Renown to the Bazaar and the Forum, then opens the search' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(2, $renown, 'two locations');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $renown[0]->location, 'bazaar');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[1]->location, 'forum');
                Assert::same(1, $renown[0]->amount, 'one each');
                Assert::false($renown[0]->isMove, 'added, not moved');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03005', $transitions[0]->transition, 'search');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'another scheme resolving does nothing' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _03006(), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);
                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // WHY: The discard checked is the active player's, not the scheme controller's.
            'taking a Gang, Crime, or Villainous card from the active player discard' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $gang = $world->placeCharacter(new GenericCharacter('Crew', ['Gang']), $world->game->getPlayerDiscardDeckName(1), 1);
                $crime = $world->placeCard(new _02061(), $world->game->getPlayerDiscardDeckName(1), 1);
                $crime->addTrait($world->game, 'Crime', true);

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '', $gang->Id);
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::same($gang->Id, $removed[0]->cardId, 'removed');
                Assert::same($gang->Id, $added[0]->cardId, 'to hand');
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');

                $world->game->activePlayerId = 2;
                $threw = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '', $crime->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'player 1 discard is not the active discard');
            },

            'a card without those traits, or not in the discard, is rejected' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $plain = $world->placeCard(new _02061(), $world->game->getPlayerDiscardDeckName(1), 1);
                $threw = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '', $plain->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'traits');

                $villainous = $world->placeCard(new _03005(), Game::LOCATION_HAND, 1);
                $threw = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '', $villainous->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in discard');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'pass is refused while a matching card remains, and allowed when the discard has none' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03005(), Game::LOCATION_PLAYER_HOME, 1);
                $gang = $world->placeCharacter(new GenericCharacter('Crew', ['Gang']), $world->game->getPlayerDiscardDeckName(1), 1);
                $threw = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must choose');

                $gang->Location = Game::LOCATION_HAND;
                $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_03005, 'x', '');
                Assert::same([''], $world->game->gamestate->transitions, 'pass');
            },
        ];
    }
}
