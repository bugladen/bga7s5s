<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;

class Card_01010_Test extends TestCase
{
    public function name(): string
    {
        return '_01010 Sarafina';
    }

    public function tests(): array
    {
        return [
            'constructs with Technique_01010 and stats' => function () {
                $sarafina = new _01010();
                Assert::instanceOf(IHasTechniques::class, $sarafina, 'has techniques');
                Assert::same(5, $sarafina->Resolve, 'Resolve');
                Assert::same(2, $sarafina->Combat, 'Combat');
                Assert::same(2, $sarafina->Finesse, 'Finesse');
                Assert::same(1, $sarafina->Influence, 'Influence');
                Assert::true($sarafina->hasTrait('Spy'), 'Spy');
                Assert::true($sarafina->hasTrait('Vodacce'), 'Vodacce');
                Assert::instanceOf(Technique_01010::class, $sarafina->getTechniques()[0], 'Technique_01010');
            },

            'gambling as Sarafina reveals +1 card' => function () {
                $world = new TestWorld();
                $sarafina = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                $count = $sarafina->getNumberOfGambleCardsToReveal($world->theah, $sarafina, $explanations);
                // Character parent returns 0 from TechniqueTrait path; card adds +1
                // Actual duel base comes from Theah; card contribution is +1 when actor is self.
                Assert::same(1, $count, 'Sarafina adds +1');
                Assert::count(1, $explanations, 'explanation recorded');
            },

            'gambling as another character does not add Sarafina bonus' => function () {
                $world = new TestWorld();
                $sarafina = $world->placeCharacter(new _01010(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $explanations = [];
                $count = $sarafina->getNumberOfGambleCardsToReveal($world->theah, $other, $explanations);
                Assert::same(0, $count, 'no bonus for other actor');
                Assert::count(0, $explanations, 'no explanation');
            },

            'state constant for technique choose' => function () {
                Assert::same(52101010, States::DUEL_CHOOSE_TECHNIQUE_01010, 'state id');
            },
        ];
    }
}
