<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01055_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01055';
    }

    /** @return array{0:_01055,1:Maneuver_01055,2:Character,3:Character} */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01055(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01055 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function equip(TestWorld $world, Character $host, object $attachment): void
    {
        $placed = $world->placeCard($attachment, Game::LOCATION_CITY_DOCKS, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
    }

    public function tests(): array
    {
        return [
            'is a Ranged ability' => function () {
                Assert::instanceOf(IRangedAbility::class, new Maneuver_01055(), 'IRangedAbility');
            },

            'available when participant has a Ranged Weapon' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $this->equip($world, $actor, new _01049());
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'ranged weapon');
            },

            'unavailable without any attachment' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'bare');
            },

            // WHY: needs Weapon AND Ranged — Kaspar's Panzerhand is Armor, not a Weapon.
            'unavailable with non-Weapon attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $this->equip($world, $actor, new _01047());
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'armor only');
            },

            'unavailable when the adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world);
                $this->equip($world, $actor, new _01049());
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'resolve wounds adversary and queues RangedAbilityPlayed' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');

                $ranged = $world->theah->queuedOfType(EventRangedAbilityPlayed::class);
                Assert::count(1, $ranged, 'ranged played');
                Assert::same(1, $ranged[0]->playerId, 'controller');
                Assert::same($risk->Id, $ranged[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $ranged[0]->abilityId, 'ability');
                Assert::same($actor->Id, $ranged[0]->performerId, 'performer');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}