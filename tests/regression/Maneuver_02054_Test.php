<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_02054_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_02054';
    }

    /**
     * @return array{0:_02054,1:Maneuver_02054,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $pistol = $world->placeCard(new _02054(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Shooter'), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->duelActor = $actor;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Maneuver_02054 $maneuver */
        $maneuver = $pistol->getManeuvers()[0];
        return [$pistol, $maneuver, $actor];
    }

    private function resolve(TestWorld $world, Maneuver_02054 $maneuver): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            // WHY: Printed text says "from your dueling line". Availability never reads Location.
            // It only requires a duel and a host the attachment can legally equip to.
            'available in a duel even while the pistol is still in hand' => function () {
                $world = new TestWorld();
                [$pistol, $maneuver] = $this->scene($world);
                Assert::same(Game::LOCATION_HAND, $pistol->Location, 'hand');
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duel');
            },

            'unavailable when the actor cannot receive attachments' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $blocked = $world->placeCharacter(new class('Blocked') extends GenericCharacter {
                    public function canReceiveAttachments(): bool
                    {
                        return false;
                    }
                }, Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $blocked;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'blocked host');
            },

            // WHY: createAttachmentEquippedEvent returns the Equipping event. Discount is the
            // printed Wealth and cost is 0. Draw uses ModifiedCombat, and 1 still draws.
            'resolve equips to the actor and draws when Combat is 1 or less' => function () {
                $world = new TestWorld();
                [$pistol, $maneuver, $actor] = $this->scene($world);
                $actor->ModifiedCombat = 1;
                $this->resolve($world, $maneuver);

                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                $draw = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $equip, 'equipping');
                Assert::same($actor->Id, $equip[0]->characterId, 'actor');
                Assert::same($pistol->Id, $equip[0]->attachmentId, 'pistol');
                Assert::same($pistol->WealthCost, $equip[0]->discount, 'discount is the wealth cost');
                Assert::same(0, $equip[0]->cost, 'cost');
                Assert::false($equip[0]->asAction, 'not an action');
                Assert::same($pistol->Id, $equip[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $equip[0]->abilityId, 'ability');
                Assert::count(1, $draw, 'draw');
                Assert::same($actor->ControllerId, $draw[0]->playerId, 'drawer');
                Assert::same($pistol->getInjectCode(), $draw[0]->reason, 'reason');
            },

            'Combat 2 equips without a draw' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->scene($world);
                $actor->ModifiedCombat = 2;
                $this->resolve($world, $maneuver);
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'equip');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
            },

            'ignores a different maneuver' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->scene($world);
                $event = new EventResolveManeuver();
                $event->maneuverId = 'other';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },
        ];
    }
}
