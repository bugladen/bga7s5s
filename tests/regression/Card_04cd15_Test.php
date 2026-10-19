<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd15;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_04cd15_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd15 Syrneth Puzzle Box';
    }

    public function tests(): array
    {
        return [
            'is a unique syrneth artifact trinket' => function () {
                $card = new _04cd15();
                Assert::instanceOf(CityAttachment::class, $card, 'CityAttachment');
                Assert::same(15, $card->CityCardNumber, 'city number');
                Assert::same(1, $card->WealthCost, 'wealth');
                Assert::true($card->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($card->hasTrait('Artifact'), 'Artifact');
                Assert::true($card->hasTrait('Trinket'), 'Trinket');
                Assert::true($card->hasTrait('Unique'), 'Unique');
            },

            // WHY: Sorcerer is granted on equip and removed on unequip so it cannot leak.
            'equip grants Sorcerer and unequip removes it' => function () {
                $world = new TestWorld();
                $box = $world->placeCard(new _04cd15(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $box->Id;
                $equipped->characterId = $host->Id;
                $world->fireOn($box, $equipped);
                Assert::true($host->hasTrait('Sorcerer'), 'granted');

                $other = new EventAttachmentEquipped();
                $other->attachmentId = 999;
                $other->characterId = $host->Id;
                $world->fireOn($box, $other);
                Assert::true($host->hasTrait('Sorcerer'), 'other equip ignored');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->attachmentId = $box->Id;
                $unequipped->characterId = $host->Id;
                $world->fireOn($box, $unequipped);
                Assert::false($host->hasTrait('Sorcerer'), 'removed');
            },
        ];
    }
}
