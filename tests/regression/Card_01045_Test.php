<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01045_Test extends TestCase
{
    public function name(): string
    {
        return '_01045 The Song of Eisen';
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Reaction_01045' => function () {
                $scheme = new _01045();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(67, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Bargain'), 'Bargain');
                Assert::true($scheme->hasTrait('Prepared'), 'Prepared');
                Assert::true($scheme->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Reaction_01045::class, $scheme->getReactions()[0], 'Reaction_01045');
            },

            'resolve adds Forum renown and transitions 01045' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'forum renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'forum');
                Assert::same('01045', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'getParleyDiscount +1 when Leader parleying and scheme at home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                $discount = $scheme->getParleyDiscount($world->theah, $leader, true, $explanations);
                Assert::same(1, $discount, 'leader parley');
            },

            'getParleyDiscount no bonus when not parleying' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = $world->placeCharacter(new _01035(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(0, $scheme->getParleyDiscount($world->theah, $leader, false, $explanations), 'not parley');
            },

            'getParleyDiscount no bonus for non-Leader' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01045(), Game::LOCATION_PLAYER_HOME, 1);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                Assert::same(0, $scheme->getParleyDiscount($world->theah, $crew, true, $explanations), 'crew');
            },

            'state constant registered' => function () {
                Assert::same(2601045, States::PLANNING_PHASE_RESOLVE_SCHEMES_01045, 'scheme state');
            },
        ];
    }
}
