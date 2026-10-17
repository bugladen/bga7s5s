<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02042;

class Card_02042_Test extends TestCase
{
    public function name(): string
    {
        return '_02042 Ivy';
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Sorcerer with a City Reaction' => function () {
                $ivy = new _02042();
                Assert::instanceOf(Character::class, $ivy, 'Character');
                Assert::instanceOf(IHasReactions::class, $ivy, 'reactions');
                Assert::same('Rune Breaker', $ivy->Title, 'title');
                Assert::same(4, $ivy->Resolve, 'Resolve');
                Assert::same(2, $ivy->Combat, 'Combat');
                Assert::same(1, $ivy->Finesse, 'Finesse');
                Assert::same(2, $ivy->Influence, 'Influence');
                Assert::false($ivy->DashedInfluence, 'solid Influence');
                Assert::true($ivy->hasFaction('Ussura'), 'Ussura');
                Assert::true($ivy->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($ivy->hasTrait('Vala'), 'Vala');
                Assert::true($ivy->hasTrait('Pirate'), 'Pirate');
                Assert::true($ivy->hasTrait('Vesten'), 'Vesten');
                Assert::instanceOf(Reaction_02042::class, $ivy->getReactions()[0], 'reaction');
            },

            'owns the reaction when placed' => function () {
                $world = new TestWorld();
                $ivy = $world->placeCharacter(new _02042(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($ivy->Id, $ivy->getReactions()[0]->OwnerId, 'owner');
            },

            // WHY: +1 only for Ivy's controller, and only while she is in the city at the gambler's location.
            'friendly participant at Ivy location reveals one extra card' => function () {
                $world = new TestWorld();
                $ivy = $world->placeCharacter(new _02042(), Game::LOCATION_CITY_DOCKS, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $explanations = [];
                $count = $ivy->getNumberOfGambleCardsToReveal($world->theah, $actor, $explanations);

                Assert::same(1, $count, 'plus one');
                Assert::count(1, $explanations, 'explained');
            },

            'no extra card for an opponent, a different location, or Ivy outside the city' => function () {
                $world = new TestWorld();
                $ivy = $world->placeCharacter(new _02042(), Game::LOCATION_CITY_DOCKS, 1);
                $opponent = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 1);

                $explanations = [];
                Assert::same(0, $ivy->getNumberOfGambleCardsToReveal($world->theah, $opponent, $explanations), 'opponent');
                Assert::same(0, $ivy->getNumberOfGambleCardsToReveal($world->theah, $elsewhere, $explanations), 'elsewhere');

                $ivy->Location = Game::LOCATION_HAND;
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_HAND, 1);
                Assert::same(0, $ivy->getNumberOfGambleCardsToReveal($world->theah, $ally, $explanations), 'not in city');
            },
        ];
    }
}
