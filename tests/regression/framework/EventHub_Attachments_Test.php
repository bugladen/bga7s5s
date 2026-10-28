<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

/**
 * EventHub: attachment equip / unequip / move / steal-release.
 *
 * WHY: releaseAttachmentStillOnOtherCharacters exists because Syrneth Hand can veto
 * unequip while the steal-equip still fires — without the strip, the old host keeps
 * the stat bonus. Pin that path; card suites rarely exercise the veto+equip race.
 */
class EventHub_Attachments_Test extends TestCase
{
    public function name(): string
    {
        return 'EventHub: attachments (equip, unequip, steal release, move)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    public function tests(): array
    {
        return [
            // WHY: EventFactory::createAttachmentEquippedEvent builds EventAttachmentEquipping
            // (pre-event). The hub turns that into EventAttachmentEquipped on process.
            'AttachmentEquipping queues AttachmentEquipped with copied fields' => function () {
                $world = $this->world();
                $event = EventFactory::createAttachmentEquippedEvent(
                    1,
                    10,
                    20,
                    1,
                    2,
                    true,
                    'discount note',
                    false,
                    99,
                    'equip'
                );
                Assert::instanceOf(EventAttachmentEquipping::class, $event, 'factory type');
                $world->applyHub($event);

                $queued = $world->theah->queuedOfType(EventAttachmentEquipped::class);
                Assert::count(1, $queued, 'queued');
                /** @var EventAttachmentEquipped $eq */
                $eq = $queued[0];
                Assert::same(10, $eq->characterId, 'char');
                Assert::same(20, $eq->attachmentId, 'att');
                Assert::same(1, $eq->discount, 'discount');
                Assert::same(2, $eq->cost, 'cost');
                Assert::same('discount note', $eq->explanations, 'explanations');
            },

            'AttachmentEquipped attaches, moves onto host, notifies fromHand' => function () {
                $world = $this->world();
                $host = $world->placeCharacter(new GenericCharacter('Wearer'), Game::LOCATION_CITY_FORUM, 1);
                $att = new GenericAttachment('Sword');
                $att->CombatModifier = 2;
                $world->placeCard($att, Game::LOCATION_HAND, 1);

                $eq = new EventAttachmentEquipped();
                $eq->playerId = 1;
                $eq->characterId = $host->Id;
                $eq->attachmentId = $att->Id;
                $eq->discount = 0;
                $eq->cost = 1;
                $eq->asAction = true;
                $world->applyHub($eq);

                Assert::true(in_array($att->Id, $host->Attachments, true), 'on host');
                Assert::same($host->Id, $att->AttachedToId, 'attachedTo');
                Assert::same(Game::LOCATION_CITY_FORUM, $att->Location, 'at site');
                Assert::same(1, $att->ControllerId, 'controller');
                Assert::same(3, $host->ModifiedCombat, 'base 1 +2');
                Assert::same('attachmentEquipped', $world->game->notify->messages[0]['type'], 'type');
                Assert::true($world->game->notify->messages[0]['args']['fromHand'], 'fromHand');
            },

            'AttachmentEquipped strips attachment still listed on another character' => function () {
                $world = $this->world();
                $old = $world->placeCharacter(new GenericCharacter('Old Host'), Game::LOCATION_CITY_DOCKS, 2);
                $new = $world->placeCharacter(new GenericCharacter('New Host'), Game::LOCATION_CITY_FORUM, 1);
                $att = new GenericAttachment('Stolen Blade');
                $att->CombatModifier = 3;
                $world->placeCard($att, Game::LOCATION_HAND, 1);

                // Simulate vetoed unequip: old host still lists the attachment + bonus.
                $old->Attachments[] = $att->Id;
                $old->ModifiedCombat += 3;
                $att->AttachedToId = $old->Id;

                $eq = new EventAttachmentEquipped();
                $eq->playerId = 1;
                $eq->characterId = $new->Id;
                $eq->attachmentId = $att->Id;
                $eq->discount = 0;
                $eq->cost = 0;
                $eq->asAction = false;
                $world->applyHub($eq);

                Assert::false(in_array($att->Id, $old->Attachments, true), 'stripped from old');
                Assert::same(1, $old->ModifiedCombat, 'old bonus removed');
                Assert::true(in_array($att->Id, $new->Attachments, true), 'on new');
                Assert::same($new->Id, $att->AttachedToId, 'attachedTo new');

                $types = array_column($world->game->notify->messages, 'type');
                Assert::true(in_array('attachmentUnequipped', $types, true), 'statCorrection unequip notify');
                $unequip = null;
                foreach ($world->game->notify->messages as $msg) {
                    if ($msg['type'] === 'attachmentUnequipped') {
                        $unequip = $msg;
                        break;
                    }
                }
                Assert::true(($unequip['args']['statCorrection'] ?? false) === true, 'statCorrection flag');
            },

            'AttachmentUnequipped removes bonus and queues destroy when wounds lethal' => function () {
                $world = $this->world();
                $host = $world->placeCharacter(new GenericCharacter('Wounded'), Game::LOCATION_CITY_FORUM, 1);
                $att = new GenericAttachment('Spaulders');
                $att->ResolveModifier = 2;
                $world->placeCard($att, Game::LOCATION_CITY_FORUM, 1);
                $host->addAttachment($world->theah, $att);
                $att->AttachedToId = $host->Id;
                // Resolve 3 +2 = 5 modified; 5 wounds → lethal once spaulders leave.
                $host->Wounds = 5;

                $world->applyHub(EventFactory::createAttachmentUnequippedEvent(1, $host->Id, $att->Id));

                Assert::same([], array_values($host->Attachments), 'unequipped');
                Assert::same(0, $att->AttachedToId, 'detached');
                Assert::true($host->IsDying, 'dying');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'destroy queued');
            },

            'AttachmentMoved transfers between characters and can kill the donor' => function () {
                $world = $this->world();
                $from = $world->placeCharacter(new GenericCharacter('Donor'), Game::LOCATION_CITY_DOCKS, 1);
                $to = $world->placeCharacter(new GenericCharacter('Receiver'), Game::LOCATION_CITY_DOCKS, 1);
                $att = new GenericAttachment('Shield');
                $att->ResolveModifier = 2;
                $world->placeCard($att, Game::LOCATION_CITY_DOCKS, 1);
                $from->addAttachment($world->theah, $att);
                $att->AttachedToId = $from->Id;
                $from->Wounds = 5;

                $world->applyHub(EventFactory::createAttachmentMovedEvent(1, $att->Id, $from->Id, $to->Id));

                Assert::false(in_array($att->Id, $from->Attachments, true), 'left donor');
                Assert::true(in_array($att->Id, $to->Attachments, true), 'on receiver');
                Assert::same($to->Id, $att->AttachedToId, 'attachedTo');
                Assert::true($from->IsDying, 'donor dying');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterDestroyed::class), 'destroy');
            },
        ];
    }
}
