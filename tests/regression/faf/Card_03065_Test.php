<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03065;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_03065_Test extends TestCase
{
    public function name(): string
    {
        return '_03065 Lodestone';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral compass attachment' => function () {
                $card = new _03065();
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Trinket'), 'Trinket');
                Assert::true($card->hasTrait('Compass'), 'Compass');
                Assert::instanceOf(Action_03065::class, $card->getActions()[0], 'action');
            },

            'equipping stamps lodestone and unequipping clears it' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $stone = $world->placeCard(new _03065(), Game::LOCATION_CITY_DOCKS, 1);
                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $stone->Id;
                $equipped->characterId = $host->Id;
                $world->fireOn($stone, $equipped);
                Assert::true($host->hasCondition(Game::LODESTONE_CONDITION), 'stamped');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->attachmentId = $stone->Id;
                $unequipped->characterId = $host->Id;
                $world->fireOn($stone, $unequipped);
                Assert::false($host->hasCondition(Game::LODESTONE_CONDITION), 'cleared');
            },
        ];
    }
}
