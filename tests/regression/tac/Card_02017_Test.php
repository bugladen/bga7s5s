<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02017;

class Card_02017_Test extends TestCase
{
    public function name(): string
    {
        return '_02017 Panzerhand';
    }

    public function tests(): array
    {
        return [
            'constructs Eisen FactionAttachment with Reaction and Technique' => function () {
                $panzer = new _02017();
                Assert::instanceOf(FactionAttachment::class, $panzer, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $panzer, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $panzer, 'techniques');
                Assert::same(1, $panzer->WealthCost, 'Wealth');
                Assert::same(1, $panzer->Riposte, 'Riposte');
                Assert::same(0, $panzer->Parry, 'Parry');
                Assert::same(2, $panzer->Thrust, 'Thrust');
                Assert::true($panzer->OffHand, 'OffHand');
                Assert::true($panzer->hasFaction('Eisen'), 'Eisen');
                Assert::true($panzer->hasTrait('Armor'), 'Armor');
                Assert::true($panzer->hasTrait('Eisenfaust'), 'Eisenfaust');
                Assert::instanceOf(Reaction_02017::class, $panzer->getReactions()[0], 'Reaction');
                Assert::instanceOf(Technique_02017::class, $panzer->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $panzer = $world->placeCard(new _02017(), Game::LOCATION_HAND, 1);
                Assert::same($panzer->Id, $panzer->getReactions()[0]->OwnerId, 'reaction');
                Assert::same($panzer->Id, $panzer->getTechniques()[0]->OwnerId, 'technique');
            },
        ];
    }
}
