<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02027;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_02027_Test extends TestCase
{
    public function name(): string
    {
        return '_02027 Éventail';
    }

    /**
     * @return array{0:_02027,1:GenericCharacter}
     */
    private function attach(TestWorld $world, array $traits = ['Diplomat'], bool $engaged = false): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Dip', $traits), Game::LOCATION_CITY_DOCKS, 1);
        $host->Engaged = $engaged;
        $fan = $world->placeCard(new _02027(), Game::LOCATION_CITY_DOCKS, 1);
        $fan->AttachedToId = $host->Id;
        $host->Attachments[] = $fan->Id;
        return [$fan, $host];
    }

    private function equipping(TestWorld $world, _02027 $fan, int $characterId): EventAttachmentEquipping
    {
        $event = new EventAttachmentEquipping();
        $event->attachmentId = $fan->Id;
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Attire FactionAttachment' => function () {
                $fan = new _02027();
                Assert::instanceOf(FactionAttachment::class, $fan, 'FactionAttachment');
                Assert::same(0, $fan->WealthCost, 'Wealth');
                Assert::same(1, $fan->FinesseModifier, '+1 Finesse');
                Assert::same(2, $fan->Riposte, 'Riposte');
                Assert::same(0, $fan->Parry, 'Parry');
                Assert::same(1, $fan->Thrust, 'Thrust');
                Assert::true($fan->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($fan->hasTrait('Attire'), 'Attire');
                Assert::true($fan->hasTrait('Fan'), 'Fan');
            },

            // Diplomat-only equip (eventCheck + canAttachTo).
            'eventCheck allows equip to a Diplomat' => function () {
                $world = new TestWorld();
                $dip = $world->placeCharacter(new GenericCharacter('Dip', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $fan = $world->placeCard(new _02027(), Game::LOCATION_HAND, 1);
                $fan->eventCheck($this->equipping($world, $fan, $dip->Id));
                Assert::true(true, 'no throw');
            },

            'eventCheck refuses equip to a non-Diplomat' => function () {
                $world = new TestWorld();
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $fan = $world->placeCard(new _02027(), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $fan->eventCheck($this->equipping($world, $fan, $crew->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Diplomat only');
            },

            'canAttachTo only accepts Diplomats' => function () {
                $dip = new GenericCharacter('Dip', ['Diplomat']);
                $crew = new GenericCharacter('Crew');
                $fan = new _02027();
                Assert::true($fan->canAttachTo($dip), 'Diplomat');
                Assert::false($fan->canAttachTo($crew), 'non-Diplomat');
            },

            // Equipped en garde → +1 Influence.
            'equip to en garde Diplomat grants +1 Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $before = $host->ModifiedInfluence;

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $fan->Id;
                $event->characterId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same($before + 1, $host->ModifiedInfluence, '+1');
                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'mod event');
                Assert::same($before, $mods[0]->OldInfluence, 'old');
                Assert::same($before + 1, $mods[0]->NewInfluence, 'new');
            },

            'equip to engaged Diplomat does not grant Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world, ['Diplomat'], true);
                $before = $host->ModifiedInfluence;

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $fan->Id;
                $event->characterId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same($before, $host->ModifiedInfluence, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'no mod');
            },

            // Engage → -1; en garde again → +1.
            'EventCardEngaged strips the +1 Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $host->ModifiedInfluence = 2;

                $event = new EventCardEngaged();
                $event->cardId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same(1, $host->ModifiedInfluence, '-1');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'mod');
            },

            'EventCardEngarded restores +1 Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $host->ModifiedInfluence = 1;

                $event = new EventCardEngarded();
                $event->cardId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same(2, $host->ModifiedInfluence, '+1');
            },

            // WHY: EventCardMoved with engage=true sets Engaged in EventHub directly and never
            // fires EventCardEngaged — card listens to EventCardMoved+engage to strip the +1
            // (Guillén 2→4 bug without this).
            'EventCardMoved with engage strips +1 while host still en garde' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $host->Engaged = false;
                $host->ModifiedInfluence = 2;

                $event = new EventCardMoved();
                $event->cardId = $host->Id;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $event->toLocation = Game::LOCATION_CITY_FORUM;
                $event->engage = true;
                $world->fireOn($fan, $event);

                Assert::same(1, $host->ModifiedInfluence, 'stripped');
            },

            'EventCardMoved without engage does not strip Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $host->ModifiedInfluence = 2;

                $event = new EventCardMoved();
                $event->cardId = $host->Id;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $event->toLocation = Game::LOCATION_CITY_FORUM;
                $event->engage = false;
                $world->fireOn($fan, $event);

                Assert::same(2, $host->ModifiedInfluence, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'no mod');
            },

            // Unequip while en garde → -1.
            'unequip while en garde removes +1 Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world);
                $host->ModifiedInfluence = 2;

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $fan->Id;
                $event->characterId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same(1, $host->ModifiedInfluence, '-1');
            },

            'unequip while engaged does not change Influence' => function () {
                $world = new TestWorld();
                [$fan, $host] = $this->attach($world, ['Diplomat'], true);
                $host->ModifiedInfluence = 1;

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $fan->Id;
                $event->characterId = $host->Id;
                $world->fireOn($fan, $event);

                Assert::same(1, $host->ModifiedInfluence, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'no mod');
            },
        ];
    }
}
