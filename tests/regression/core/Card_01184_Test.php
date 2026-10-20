<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01184;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01184;

class Card_01184_Test extends TestCase
{
    public function name(): string
    {
        return '_01184 Claude de la Roche';
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable Mercenary Diplomat Montaigne with Reaction_01184' => function () {
                $claude = new _01184();
                Assert::instanceOf(CityCharacter::class, $claude, 'CityCharacter');
                Assert::instanceOf(IHasReactions::class, $claude, 'reactions');
                Assert::same(4, $claude->Resolve, 'Resolve');
                Assert::same(1, $claude->Combat, 'Combat');
                Assert::same(1, $claude->Finesse, 'Finesse');
                Assert::same(2, $claude->Influence, 'Influence');
                Assert::same(6, $claude->WealthCost, 'wealth');
                Assert::same(8, $claude->CityCardNumber, 'city #');
                Assert::true($claude->Negotiable, 'Negotiable');
                Assert::true($claude->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($claude->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($claude->hasTrait('Montaigne'), 'Montaigne');
                Assert::instanceOf(Reaction_01184::class, $claude->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01184 when placed' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _01184(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $claude->getReactions(), 'one');
                Assert::same($claude->Id, $claude->getReactions()[0]->OwnerId, 'owner');
            },

            // WHY (journal 2026-03-31-11): getInfluencePressureValue adds +1 only for the
            // Influence pressure path — Combat/Finesse/Resolve stay at base Modified*.
            'pressure Influence is ModifiedInfluence plus one' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _01184(), Game::LOCATION_CITY_DOCKS, 1);

                Assert::same(3, $claude->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), '2 + 1');
            },

            'pressure Influence bonus builds on ModifiedInfluence' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _01184(), Game::LOCATION_CITY_DOCKS, 1);
                $claude->ModifiedInfluence = 5;

                Assert::same(6, $claude->getInfluencePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), '5 + 1');
            },

            'other pressure stats are unaffected' => function () {
                $world = new TestWorld();
                $claude = $world->placeCharacter(new _01184(), Game::LOCATION_CITY_DOCKS, 1);

                Assert::same($claude->ModifiedCombat, $claude->getCombatPressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Combat');
                Assert::same($claude->ModifiedFinesse, $claude->getFinessePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Finesse');
                Assert::same($claude->ModifiedResolve, $claude->getResolvePressureValue($world->theah, Game::LOCATION_CITY_DOCKS), 'Resolve');
            },
        ];
    }
}
