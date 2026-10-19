<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04008_Silence;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_04008_Silence_Test extends TestCase
{
    public function name(): string
    {
        return "_04008_Silence";
    }

    public function tests(): array
    {
        return [
            'is the fake attachment the table sees' => function () {
                $card = new _04008_Silence();
                Assert::true($card->FakeAttachment, 'fake');
                Assert::false($card->ShowStatModifiers, 'no stat row');
                Assert::same(1, $card->Riposte, 'copied riposte');
                Assert::same(8, $card->CardNumber, 'same number as the risk');
            },

            // WHY: Blanking is a condition, not attachment presence. Equip calls
            // onAbilitiesBlanked immediately so a stamped aura cannot survive the skip.
            // End of High Drama destroys an equipped copy and returns the original risk.
            'equip blanks the host, unequip restores, and dusk destroys it' => function () {
                $world = new TestWorld();
                $silence = $world->placeCard(new _04008_Silence(), Game::LOCATION_CITY_DOCKS, 1);
                $original = $world->placeCard(new _04008(), Game::LOCATION_HAND, 1);
                $silence->OriginalCardId = $original->Id;
                $silence->OwnerId = 1;
                $host = $world->placeCharacter(new _04001(), Game::LOCATION_CITY_DOCKS, 2);
                $host->OpposedWoundedCombatBonusApplied = true;
                $silence->AttachedToId = $host->Id;

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $silence->Id;
                $equipped->characterId = $host->Id;
                $equipped->theah = $world->theah;
                $silence->handleEvent($equipped);
                Assert::true($host->hasCondition(Game::FATES_SILENCE_CONDITION), 'blanked');
                Assert::false($host->OpposedWoundedCombatBonusApplied, 'aura cleared');
                Assert::same(1, $world->theah->queuedOfType(EventCharacterCombatModified::class)[0]->NewCombat, 'combat removed');

                $world->theah->takeQueuedEvents();
                $unequipped = new EventAttachmentUnequipped();
                $unequipped->attachmentId = $silence->Id;
                $unequipped->characterId = $host->Id;
                $unequipped->theah = $world->theah;
                $silence->handleEvent($unequipped);
                Assert::false($host->hasCondition(Game::FATES_SILENCE_CONDITION), 'restored');

                $silence->AttachedToId = $host->Id;
                $end = new EventHighDramaPhaseEnd();
                $end->theah = $world->theah;
                $silence->handleEvent($end);
                Assert::count(1, $world->theah->queuedOfType(EventCardHidden::class), 'shell hidden');
                Assert::same('Discard-1', $original->Location, 'original risk discarded');
            },
        ];
    }
}
