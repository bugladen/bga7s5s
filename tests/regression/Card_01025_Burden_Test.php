<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01025_Burden;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;

class Card_01025_Burden_Test extends TestCase
{
    public function name(): string
    {
        return "_01025_Burden Fate's Burden attachment";
    }

    private function attachBurden(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
        $original = $world->placeCard(new _01025(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
        $burden = $world->placeCard(new _01025_Burden(), Game::LOCATION_CITY_DOCKS, 1);
        $burden->AttachedToId = $host->Id;
        $burden->setOriginalCardId($original->Id);
        return [$host, $burden, $original];
    }

    public function tests(): array
    {
        return [
            'constructs fake RiskAttachment without stat modifiers' => function () {
                $burden = new _01025_Burden();
                Assert::instanceOf(Attachment::class, $burden, 'Attachment');
                Assert::instanceOf(IRiskAttachment::class, $burden, 'IRiskAttachment');
                Assert::true($burden->FakeAttachment, 'FakeAttachment');
                Assert::false($burden->ShowStatModifiers, 'ShowStatModifiers');
                Assert::true($burden->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($burden->hasTrait('Sorte'), 'Sorte');
            },

            // WHY: "when would en garde" — cancel EventCardEngarded and destroy attachment
            'cancels EventCardEngarded on host and removes attachment' => function () {
                $world = new TestWorld();
                [$host, $burden] = $this->attachBurden($world);

                $event = new EventCardEngarded();
                $event->cardId = $host->Id;
                $world->fireOn($burden, $event);

                Assert::true($event->canceled, 'en garde canceled');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::same(
                    $world->game->getPlayerDiscardDeckName(1),
                    $world->theah->getCardById($burden->OriginalCardId)->Location,
                    'original risk to discard'
                );
            },

            'does not cancel EventCardEngaged (Engage)' => function () {
                $world = new TestWorld();
                [$host, $burden] = $this->attachBurden($world);

                $event = new EventCardEngaged();
                $event->cardId = $host->Id;
                $world->fireOn($burden, $event);

                Assert::false($event->canceled, 'Engage not canceled');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'no unequip');
            },

            'destroys itself at end of High Drama while attached' => function () {
                $world = new TestWorld();
                [, $burden] = $this->attachBurden($world);

                $event = new EventHighDramaPhaseEnd();
                $world->fireOn($burden, $event);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'HD end unequip');
            },
        ];
    }
}
