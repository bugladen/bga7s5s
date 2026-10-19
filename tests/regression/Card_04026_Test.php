<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;

class Card_04026_Test extends TestCase
{
    public function name(): string
    {
        return '_04026 Pompon';
    }

    public function tests(): array
    {
        return [
            // WHY: Equip is Diplomat-only. eventCheck does not walk cards, so the
            // restriction is called directly. Dashed riposte and dashed thrust are printed.
            'equips only to a diplomat' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04026(), 'hand', 1);
                Assert::instanceOf(FactionAttachment::class, $card, 'attachment');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(1, $card->ResolveModifier, 'resolve');
                Assert::same(1, $card->Parry, 'parry');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::true($card->DashedThrust, 'dashed thrust');
                Assert::true($card->hasTrait('Pet'), 'Pet');
                Assert::count(1, $card->getReactions(), 'reaction');
                Assert::count(1, $card->getTechniques(), 'technique');

                $diplomat = $world->placeCharacter(new GenericCharacter('Envoy', ['Diplomat']), 'City Docks', 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), 'City Docks', 1);
                Assert::true($card->canAttachTo($diplomat), 'diplomat');
                Assert::false($card->canAttachTo($hero), 'not a diplomat');

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
                $equipping->characterId = $diplomat->Id;
                $card->eventCheck($equipping);
            },
        ];
    }
}
