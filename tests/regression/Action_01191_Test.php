<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01191;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01191;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;

class Action_01191_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01191';
    }

    /**
     * @return array{0:_01191,1:Action_01191,2:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01191 $pistol */
        $pistol = $world->placeCard(new _01191(), Game::LOCATION_CITY_DOCKS, 1);
        $pistol->AttachedToId = $host->Id;
        $host->Attachments[] = $pistol->Id;
        /** @var Action_01191 $action */
        $action = $pistol->getActions()[0];
        return [$pistol, $action, $host];
    }

    private function trigger(TestWorld $world, Action_01191 $action, int $playerId = 1): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction and IRangedAbility' => function () {
                $action = new Action_01191();
                Assert::instanceOf(AttachmentAction::class, $action, 'AttachmentAction');
                Assert::instanceOf(IRangedAbility::class, $action, 'ranged');
            },

            'available when attached to a character' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when not attached' => function () {
                $world = new TestWorld();
                $pistol = $world->placeCard(new _01191(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01191 $action */
                $action = $pistol->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            // WHY (journal 2026-04-08-01): cost before effect — unequip + city discard (asEffect=false),
            // then wound every non-Leader at the location, then ranged + ActionResolved.
            'trigger destroys pistol, wounds non-Leaders, fires ranged and ActionResolved' => function () {
                $world = new TestWorld();
                [$pistol, $action, $host] = $this->equipped($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 2);

                $this->trigger($world, $action);

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($pistol->Id, $unequip[0]->attachmentId, 'pistol');
                Assert::same($host->Id, $unequip[0]->characterId, 'host');

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'city discard');
                Assert::same($pistol->Id, $discards[0]->cardId, 'pistol');
                Assert::false($discards[0]->asEffect, 'destroy is cost');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'host + foe, not Leader');
                $woundIds = array_map(fn($e) => $e->characterId, $wounds);
                Assert::true(in_array($host->Id, $woundIds, true), 'host wounded');
                Assert::true(in_array($foe->Id, $woundIds, true), 'foe wounded');
                Assert::false(in_array($leader->Id, $woundIds, true), 'Leader spared');
                foreach ($wounds as $wound) {
                    Assert::same(1, $wound->wounds, '1 wound');
                    Assert::same($pistol->Id, $wound->sourceId, 'source pistol');
                    Assert::same($action->Id, $wound->abilityId, 'ability');
                }

                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::true(
                    array_search(EventAttachmentUnequipped::class, $order, true)
                        < array_search(EventCardAddedToCityDiscardPile::class, $order, true),
                    'unequip before discard'
                );
                Assert::true(
                    array_search(EventCardAddedToCityDiscardPile::class, $order, true)
                        < array_search(EventCharacterBeingWounded::class, $order, true),
                    'cost before wounds'
                );
            },

            // WHY (journal 2026-05-10): copied Action may live on a Character (not Attachment).
            // isAttached() destroy path is skipped; wounds + resolve still fire.
            'trigger from Character-owned copy wounds without destroy' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->equipped($world);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $action->OwnerId = $host->Id;

                $this->trigger($world, $action);

                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'no unequip');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'no discard');
                Assert::count(2, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wounds');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'trigger for another action id does nothing' => function () {
                $world = new TestWorld();
                [, $action] = $this->equipped($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
