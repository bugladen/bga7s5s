<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01153;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;

class Card_01153_Test extends TestCase
{
    public function name(): string
    {
        return '_01153 Breastplate';
    }

    private function blockedFlag(_01153 $plate): bool
    {
        $prop = new \ReflectionProperty(_01153::class, 'hasBlockedWound');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($plate);
    }

    private function setBlockedFlag(_01153 $plate, bool $value): void
    {
        $prop = new \ReflectionProperty(_01153::class, 'hasBlockedWound');
        $prop->setAccessible(true);
        $prop->setValue($plate, $value);
    }

    /**
     * @return array{0:_01153,1:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01153 $plate */
        $plate = $world->placeCard(new _01153(), Game::LOCATION_CITY_DOCKS, 1);
        $plate->AttachedToId = $host->Id;
        $host->Attachments[] = $plate->Id;
        return [$plate, $host];
    }

    private function beingWounded(TestWorld $world, int $characterId, int $wounds): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->characterId = $characterId;
        $event->wounds = $wounds;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Armor FactionAttachment with Parry 3 / Thrust 1' => function () {
                $plate = new _01153();
                Assert::instanceOf(FactionAttachment::class, $plate, 'FactionAttachment');
                Assert::same(1, $plate->WealthCost, 'wealth');
                Assert::true($plate->DashedRiposte, 'dashed Riposte');
                Assert::same(3, $plate->Parry, 'Parry');
                Assert::same(1, $plate->Thrust, 'Thrust');
                Assert::true($plate->hasTrait('Armor'), 'Armor');
                Assert::false($this->blockedFlag($plate), 'flag starts false');
            },

            // WHY (production _01153.php): wound reduction runs in eventCheck, not handleEvent,
            // so Reaction clones of EventCharacterBeingWounded see the reduced wounds.
            'eventCheck in a duel reduces first wound by one and persists hasBlockedWound' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = $this->beingWounded($world, $host->Id, 2);
                $plate->eventCheck($event);

                Assert::same(1, $event->wounds, 'reduced');
                Assert::true($this->blockedFlag($plate), 'flag set');
                Assert::true($plate->IsUpdated, 'dirty');
                // WHY: leftover-threat wounds are queued outside runEvents; flag must hit DB now.
                Assert::true(in_array($plate->Id, $world->game->updatedCardObjectIds, true), 'immediate DB write');
            },

            'eventCheck does not reduce wounds outside a duel' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);
                $world->game->globals->set(Game::IN_DUEL, false);

                $event = $this->beingWounded($world, $host->Id, 2);
                $plate->eventCheck($event);

                Assert::same(2, $event->wounds, 'unchanged');
                Assert::false($this->blockedFlag($plate), 'flag unset');
                Assert::false(in_array($plate->Id, $world->game->updatedCardObjectIds, true), 'no DB write');
            },

            'eventCheck does not reduce a second wound in the same duel' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                $this->setBlockedFlag($plate, true);

                $event = $this->beingWounded($world, $host->Id, 2);
                $plate->eventCheck($event);

                Assert::same(2, $event->wounds, 'already blocked');
            },

            'eventCheck ignores wounds for a different character' => function () {
                $world = new TestWorld();
                [$plate] = $this->equipped($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = $this->beingWounded($world, $other->Id, 2);
                $plate->eventCheck($event);

                Assert::same(2, $event->wounds, 'other host');
                Assert::false($this->blockedFlag($plate), 'flag unset');
            },

            // WHY: handleEvent must NOT also reduce — that was the regression (dbbf159e).
            'handleEvent on BeingWounded does not reduce wounds' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = $this->beingWounded($world, $host->Id, 2);
                $world->fireOn($plate, $event);

                Assert::same(2, $event->wounds, 'handleEvent leaves wounds');
                Assert::false($this->blockedFlag($plate), 'flag unset');
            },

            // WHY: per-duel "first time"; without reset a 1→0 block leaves the card inert forever.
            'EventDuelEnd resets hasBlockedWound' => function () {
                $world = new TestWorld();
                [$plate] = $this->equipped($world);
                $this->setBlockedFlag($plate, true);

                $end = new EventDuelEnd();
                $world->fireOn($plate, $end);

                Assert::false($this->blockedFlag($plate), 'reset');
                Assert::true($plate->IsUpdated, 'dirty');
            },

            'EventCharacterWounded with wounds>0 unequips and destroys Breastplate' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);

                $event = new EventCharacterWounded();
                $event->characterId = $host->Id;
                $event->wounds = 1;
                $world->fireOn($plate, $event);

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($plate->Id, $unequip[0]->attachmentId, 'attachment');

                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discard, 'destroy');
                Assert::same($plate->Id, $discard[0]->cardId, 'plate');
            },

            // WHY: card text "(0 wounds is not wounded.)" — Breastplate survives a 0-wound event.
            'EventCharacterWounded with 0 wounds does not destroy' => function () {
                $world = new TestWorld();
                [$plate, $host] = $this->equipped($world);

                $event = new EventCharacterWounded();
                $event->characterId = $host->Id;
                $event->wounds = 0;
                $world->fireOn($plate, $event);

                Assert::count(0, $world->theah->queuedEvents, 'survives');
            },
        ];
    }
}
