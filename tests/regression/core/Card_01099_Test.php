<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01099;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01099a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01099b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_01099_Test extends TestCase
{
    public function name(): string
    {
        return '_01099 Shifting Blame';
    }

    private function resolve(TestWorld $world, _01099|_01098 $scheme, _01099|_01098 $revealed): void
    {
        $event = new EventResolveScheme();
        $event->scheme = $revealed;
        $event->playerId = $scheme->ControllerId;
        $event->playerName = 'Player One';
        $event->theah = $world->theah;
        $scheme->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'constructs Castille Cunning Rumor Scheme with Initiative 10 and Panache 0' => function () {
                $scheme = new _01099();
                Assert::same(10, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache modifier');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Cunning'), 'Cunning');
                Assert::true($scheme->hasTrait('Rumor'), 'Rumor');
            },

            'exposes the discard-draw and claim-renown reactions in order' => function () {
                $scheme = new _01099();
                $reactions = $scheme->getReactions();
                Assert::count(2, $reactions, 'two reactions');
                Assert::instanceOf(Reaction_01099a::class, $reactions[0], 'discard draw');
                Assert::instanceOf(Reaction_01099b::class, $reactions[1], 'claim renown');
            },

            'resolving adds exactly one Renown to The Docks for its controller' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 1);

                $this->resolve($world, $scheme, $scheme);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'one Renown event');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'The Docks');
                Assert::same(1, $added[0]->amount, '1 Renown');
                Assert::same(1, $added[0]->playerId, 'controller');
                Assert::same($scheme->getInjectCode(), $added[0]->description, 'attributed to the scheme');
                Assert::false($added[0]->isMove, 'new Renown, not a move');
            },

            'resolving announces the scheme' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 1);

                $this->resolve($world, $scheme, $scheme);

                Assert::count(1, $world->game->notify->messages, 'one notification');
                Assert::same($scheme->getInjectCode(), $world->game->notify->messages[0]['args']['scheme_inject_code'], 'inject code');
            },

            'resolving a different scheme does not add Renown' => function () {
                $world = new TestWorld();
                $shifting = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 2);

                $this->resolve($world, $shifting, $other);

                Assert::count(0, $world->theah->queuedEvents, 'not this scheme');
            },
        ];
    }
}
