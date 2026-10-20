<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_03043_Test extends TestCase
{
    public function name(): string
    {
        return '_03043 El Gato\'s Mask';
    }

    public function tests(): array
    {
        return [
            'constructs a unique Castille mask' => function () {
                $mask = new _03043();
                Assert::instanceOf(FactionAttachment::class, $mask, 'attachment');
                Assert::instanceOf(IHasTechniques::class, $mask, 'techniques');
                Assert::same(1, $mask->WealthCost, 'wealth');
                Assert::same(1, $mask->InfluenceModifier, 'influence');
                Assert::same(1, $mask->Riposte, 'riposte');
                Assert::same(2, $mask->Parry, 'parry');
                Assert::same(1, $mask->Thrust, 'thrust');
                Assert::true($mask->hasFaction('Castille'), 'Castille');
                Assert::true($mask->hasTrait('Attire'), 'Attire');
                Assert::true($mask->hasTrait('Mask'), 'Mask');
                Assert::true($mask->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_03043::class, $mask->getTechniques()[0], 'technique');
            },

            'equipping grants Scoundrel, and unequipping removes it' => function () {
                $world = new TestWorld();
                $mask = $world->placeCard(new _03043(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host', ['Hero']), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($mask->canAttachTo($host), 'no duelist restriction');
                $equipped = new EventAttachmentEquipped();
                $equipped->theah = $world->theah;
                $equipped->attachmentId = $mask->Id;
                $equipped->characterId = $host->Id;
                $mask->handleEvent($equipped);
                Assert::true($host->hasTrait('Scoundrel'), 'gained');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->theah = $world->theah;
                $unequipped->attachmentId = $mask->Id;
                $unequipped->characterId = $host->Id;
                $mask->handleEvent($unequipped);
                Assert::false($host->hasTrait('Scoundrel'), 'removed');
                Assert::true($host->hasTrait('Hero'), 'other trait stays');
            },
        ];
    }
}
