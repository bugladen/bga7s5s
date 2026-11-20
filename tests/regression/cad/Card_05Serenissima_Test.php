<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Serenissima;

class Card_05Serenissima_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Serenissima';
    }

    public function tests(): array
    {
        return [
            'scaffold fields and traits match the printed Leader' => function () {
                $card = new _05Serenissima();
                Assert::same('Patricia Moustakas', $card->Name, 'Name');
                Assert::same('Tickets, Please.', $card->Title, 'Title');
                Assert::same(16, $card->CardNumber, 'CardNumber');
                Assert::same(6, $card->Resolve, 'Resolve');
                Assert::same(1, $card->Combat, 'Combat');
                Assert::same(2, $card->Finesse, 'Finesse');
                Assert::same(3, $card->Influence, 'Influence');
                Assert::same(6, $card->CrewCap, 'CrewCap');
                Assert::same(6, $card->Panache, 'Panache');
                Assert::true($card->hasTrait('Leader'), 'Leader');
                Assert::true($card->hasTrait('Hero'), 'Hero');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::true($card->hasTrait('Dockmaster'), 'Dockmaster');
                Assert::true($card->hasTrait('Numa'), 'Numa');
                Assert::count(1, $card->getActions(), 'Actions');
                Assert::count(1, $card->getTechniques(), 'Techniques');
            },

            // WHY: Uwe queryCard fiction — null query sees printed traits only; same-controller
            // queryCard sees any trait; opponent queryCard / blanked do not.
            'hasTrait fiction only when same-controller queryCard and not blanked' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $allyAbility = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $enemyAbility = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::false($patricia->hasTrait('Diplomat'), 'printed only without query');
                Assert::true($patricia->hasTrait('Diplomat', $allyAbility), 'same-controller fiction');
                Assert::false($patricia->hasTrait('Diplomat', $enemyAbility), 'opponent query');

                $patricia->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($patricia->hasTrait('Diplomat', $allyAbility), 'blanked');
            },
        ];
    }
}
