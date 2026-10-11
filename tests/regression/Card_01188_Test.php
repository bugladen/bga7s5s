<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01188;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;

class Card_01188_Test extends TestCase
{
    public function name(): string
    {
        return '_01188 Vladislav Novikoff';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable CityCharacter Mercenary with dashed Influence' => function () {
                $vlad = new _01188();
                Assert::instanceOf(CityCharacter::class, $vlad, 'CityCharacter');
                Assert::true($vlad->Negotiable, 'Negotiable');
                Assert::same(5, $vlad->Resolve, 'Resolve');
                Assert::same(0, $vlad->Combat, 'Combat');
                Assert::same(0, $vlad->Finesse, 'Finesse');
                Assert::same(0, $vlad->Influence, 'Influence');
                Assert::true($vlad->DashedInfluence, 'dashed Influence');
                Assert::same(4, $vlad->WealthCost, 'Wealth');
                Assert::same(12, $vlad->CityCardNumber, 'city #');
                Assert::true($vlad->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($vlad->hasTrait('Ussura'), 'Ussura');
                Assert::false($vlad->HasIntervened, 'not intervened');
            },

            'canIntervene while engaged when controlled' => function () {
                $world = new TestWorld();
                $vlad = $world->placeCharacter(new _01188(), Game::LOCATION_CITY_DOCKS, 1);
                $vlad->Engaged = true;
                Assert::true($vlad->canIntervene(), 'engaged ok');
            },

            'EventCharacterIntervened onto Vladislav sets HasIntervened' => function () {
                $world = new TestWorld();
                $vlad = $world->placeCharacter(new _01188(), Game::LOCATION_CITY_DOCKS, 1);
                $old = $world->placeCharacter(new GenericCharacter('Old'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventCharacterIntervened();
                $event->playerId = 1;
                $event->oldTargetId = $old->Id;
                $event->newTargetId = $vlad->Id;
                $event->theah = $world->theah;
                $vlad->handleEvent($event);

                Assert::true($vlad->HasIntervened, 'flag');
                Assert::true($vlad->IsUpdated, 'dirty');
            },

            // WHY: Round-1 NewRound after intervene stamps VLADISLAV_DUEL_TYPE so the actor
            // only gets End Duel (take threat as wounds; no combat card).
            'round-1 NewRound as actor after intervene sets VLADISLAV_DUEL_TYPE' => function () {
                $world = new TestWorld();
                $vlad = $world->placeCharacter(new _01188(), Game::LOCATION_CITY_DOCKS, 1);
                $vlad->HasIntervened = true;

                $event = new EventDuelNewRound();
                $event->round = 1;
                $event->actorId = $vlad->Id;
                $event->theah = $world->theah;
                $vlad->handleEvent($event);

                Assert::same(Game::VLADISLAV_DUEL_TYPE, (int) $world->game->globals->get(Game::DUEL_TYPE), 'type');
            },

            'NewRound without HasIntervened does not set VLADISLAV_DUEL_TYPE' => function () {
                $world = new TestWorld();
                $vlad = $world->placeCharacter(new _01188(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventDuelNewRound();
                $event->round = 1;
                $event->actorId = $vlad->Id;
                $event->theah = $world->theah;
                $vlad->handleEvent($event);

                Assert::same(0, (int) $world->game->globals->get(Game::DUEL_TYPE, 0), 'unchanged');
            },

            'DuelEnd clears HasIntervened' => function () {
                $world = new TestWorld();
                $vlad = $world->placeCharacter(new _01188(), Game::LOCATION_CITY_DOCKS, 1);
                $vlad->HasIntervened = true;

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $vlad->handleEvent($end);

                Assert::false($vlad->HasIntervened, 'cleared');
                Assert::true($vlad->IsUpdated, 'dirty');
            },
        ];
    }
}
