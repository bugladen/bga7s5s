<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;

class Card_03019_Test extends TestCase
{
    public function name(): string
    {
        return '_03019 Kaiser Schnurrbart';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen hound attachment' => function () {
                $hound = new _03019();
                Assert::instanceOf(FactionAttachment::class, $hound, 'attachment');
                Assert::instanceOf(IHasReactions::class, $hound, 'reactions');
                Assert::same(2, $hound->WealthCost, 'wealth');
                Assert::same(1, $hound->ResolveModifier, 'resolve');
                Assert::same(1, $hound->FinesseModifier, 'finesse');
                Assert::true($hound->DashedRiposte, 'dashed riposte');
                Assert::same(2, $hound->Parry, 'parry');
                Assert::same(3, $hound->Thrust, 'thrust');
                Assert::true($hound->hasFaction('Eisen'), 'Eisen');
                Assert::true($hound->hasTrait('Hound'), 'Hound');
                Assert::true($hound->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Reaction_03019::class, $hound->getReactions()[0], 'reaction');
            },

            'equipping grants Hunter, and unequipping removes it' => function () {
                $world = new TestWorld();
                $hound = $world->placeCard(new _03019(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host', ['Hero', 'Eisen']), Game::LOCATION_CITY_DOCKS, 1);
                $equipped = new EventAttachmentEquipped();
                $equipped->theah = $world->theah;
                $equipped->attachmentId = $hound->Id;
                $equipped->characterId = $host->Id;
                $hound->handleEvent($equipped);
                Assert::true($host->hasTrait('Hunter'), 'gained hunter');

                $unequipped = new EventAttachmentUnequipped();
                $unequipped->theah = $world->theah;
                $unequipped->attachmentId = $hound->Id;
                $unequipped->characterId = $host->Id;
                $hound->handleEvent($unequipped);
                Assert::false($host->hasTrait('Hunter'), 'removed');
            },
        ];
    }
}
