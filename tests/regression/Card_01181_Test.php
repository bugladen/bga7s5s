<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01181;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01181;

class Card_01181_Test extends TestCase
{
    public function name(): string
    {
        return '_01181 Sorte Deck';
    }

    public function tests(): array
    {
        return [
            'constructs Sorte Trinket CityAttachment with Reaction_01181' => function () {
                $deck = new _01181();
                Assert::instanceOf(CityAttachment::class, $deck, 'CityAttachment');
                Assert::instanceOf(IHasReactions::class, $deck, 'reactions');
                Assert::same(5, $deck->CityCardNumber, 'city #');
                Assert::same(1, $deck->WealthCost, 'wealth');
                Assert::same(0, $deck->ResolveModifier, 'Resolve');
                Assert::same(0, $deck->CombatModifier, 'Combat');
                Assert::same(0, $deck->FinesseModifier, 'Finesse');
                Assert::same(0, $deck->InfluenceModifier, 'Influence');
                Assert::true($deck->hasTrait('Sorte'), 'Sorte');
                Assert::true($deck->hasTrait('Trinket'), 'Trinket');
                Assert::instanceOf(Reaction_01181::class, $deck->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01181 when placed' => function () {
                $world = new TestWorld();
                $deck = $world->placeCard(new _01181(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $deck->getReactions(), 'one');
                Assert::same($deck->Id, $deck->getReactions()[0]->OwnerId, 'owner');
            },
        ];
    }
}
