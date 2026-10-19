<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04036;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_04036_Test extends TestCase
{
    public function name(): string
    {
        return '_04036 Ciphered Tome';
    }

    public function tests(): array
    {
        return [
            // WHY: Academic only. eventCheck is called directly because the harness
            // does not walk cards.
            'equips only to an academic' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04036(), 'hand', 1);
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->hasTrait('Codex'), 'Codex');
                Assert::count(2, $card->getActions(), 'two city actions');

                $academic = $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), 'City Docks', 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), 'City Docks', 1);
                Assert::true($card->canAttachTo($academic), 'academic');
                Assert::false($card->canAttachTo($hero), 'not an academic');

                $equipping = new EventAttachmentEquipping();
                $equipping->attachmentId = $card->Id;
                $equipping->characterId = $hero->Id;
                $equipping->theah = $world->theah;
                $threw = false;
                try {
                    $card->eventCheck($equipping);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'equip check');
                $equipping->characterId = $academic->Id;
                $card->eventCheck($equipping);
            },
        ];
    }
}
