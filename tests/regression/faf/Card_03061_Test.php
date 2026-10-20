<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03061;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03061_Test extends TestCase
{
    public function name(): string
    {
        return '_03061 Burn like Mice';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral heroic scheme' => function () {
                $card = new _03061();
                Assert::instanceOf(Scheme::class, $card, 'scheme');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(32, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Heroic'), 'Heroic');
                Assert::true($card->hasTrait('Declaration'), 'Declaration');
                Assert::same([], $card->remainingWoundIds, 'queue');
                Assert::instanceOf(Action_03061::class, $card->getActions()[0], 'action');
            },

            'resolve adds renown to the forum and the bazaar' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03061(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $locations = array_map(fn ($e) => $e->location, $world->theah->queuedOfType(EventRenownAddedToLocation::class));
                Assert::same([Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR], $locations, 'forum and bazaar');
            },

            // WHY: Forced only while the chosen scheme is at Home. One character is wounded
            // immediately. Two or more remember an order and the pick uses an empty internal id
            // so it does not collide with the City Action's "03061" transition.
            'end of high drama wounds each character at home in a chosen order' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03061(), Game::LOCATION_HAND, 1);
                $only = $world->placeCharacter(new GenericCharacter('Only'), Game::LOCATION_PLAYER_HOME, 2);
                $end = new EventHighDramaPhaseEnd();
                $world->fireOn($scheme, $end);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not chosen');

                $scheme->Location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($scheme, $end);
                Assert::same($only->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'immediate');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no pick');

                $world->theah->queuedEvents = [];
                $second = $world->placeCharacter(new GenericLeader(), Game::LOCATION_PLAYER_HOME, 1);
                $world->fireOn($scheme, $end);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'choice first');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03061', $transition->transition, 'picker');
                Assert::same('', $transition->internalId, 'scheme path');
                Assert::count(2, $scheme->remainingWoundIds, 'both');

                $second->Location = Game::LOCATION_CITY_DOCKS;
                $threw = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::HIGH_DRAMA_END_03061, 'x', '', $second->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'left home');

                $scheme->actFromCardWithId($world->game, States::HIGH_DRAMA_END_03061, 'x', '', $only->Id);
                Assert::same($only->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                Assert::same([], $scheme->remainingWoundIds, 'done');
                Assert::same([''], array_slice($world->game->gamestate->transitions, -1), 'empty transition');
            },
        ];
    }
}
