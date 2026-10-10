<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01161_Boon;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class Card_01161_Test extends TestCase
{
    public function name(): string
    {
        return '_01161 Boon';
    }

    /** @return array{0:GenericCharacter,1:_01161_Boon,2:_01161} */
    private function attachBoon(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
        $original = $world->placeCard(new _01161(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
        $boon = $world->placeCard(new _01161_Boon(), Game::LOCATION_CITY_DOCKS, 1);
        $boon->AttachedToId = $host->Id;
        $boon->setOriginalCardId($original->Id);
        return [$host, $boon, $original];
    }

    public function tests(): array
    {
        return [
            'constructs Sorcery Glamour Risk with Action_01161' => function () {
                $risk = new _01161();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(0, $risk->WealthCost, 'cost');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($risk->hasTrait('Glamour'), 'Glamour');
                Assert::instanceOf(Action_01161::class, $risk->getActions()[0], 'Action_01161');
            },

            'Boon attachment is a FakeAttachment with +1 Combat/Finesse/Influence' => function () {
                $boon = new _01161_Boon();
                Assert::instanceOf(Attachment::class, $boon, 'Attachment');
                Assert::instanceOf(IRiskAttachment::class, $boon, 'IRiskAttachment');
                Assert::true($boon->FakeAttachment, 'FakeAttachment');
                Assert::same(1, $boon->CombatModifier, '+1 Combat');
                Assert::same(1, $boon->FinesseModifier, '+1 Finesse');
                Assert::same(1, $boon->InfluenceModifier, '+1 Influence');
                Assert::true($boon->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($boon->hasTrait('Glamour'), 'Glamour');
            },

            // WHY: Card text — "At the end of the Day, discard this card." EventDuskEndOfDay
            // (not High Drama end) matches Burden's HD-end pattern but for Day end.
            'attached Boon removes itself at Dusk end of Day' => function () {
                $world = new TestWorld();
                [, $boon, $original] = $this->attachBoon($world);

                $dusk = new EventDuskEndOfDay();
                $world->fireOn($boon, $dusk);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::same(
                    $world->game->getPlayerDiscardDeckName(1),
                    $original->Location,
                    'original Risk to discard'
                );
            },

            'unattached Boon ignores Dusk end of Day' => function () {
                $world = new TestWorld();
                $boon = $world->placeCard(new _01161_Boon(), Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskEndOfDay();
                $world->fireOn($boon, $dusk);

                Assert::count(0, $world->theah->queuedEvents, 'no remove');
            },
        ];
    }
}
