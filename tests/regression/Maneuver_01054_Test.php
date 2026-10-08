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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01054_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01054';
    }

    /** @return array{0:_01054,1:Maneuver_01054,2:Character,3:Character} */
    private function duel(TestWorld $world, int $actorCombat, int $foeCombat): array
    {
        $risk = $world->placeCard(new _01054(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $actor->ModifiedCombat = $actorCombat;
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->ModifiedCombat = $foeCombat;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;

        /** @var Maneuver_01054 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function equipEisenfaust(TestWorld $world, Character $host): void
    {
        $armor = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, $host->ControllerId);
        $armor->AttachedToId = $host->Id;
        $host->Attachments[] = $armor->Id;
    }

    public function tests(): array
    {
        return [
            'available when Combat is greater than adversary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3, 1);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'greater');
            },

            // WHY: card text says "equal or greater [Combat]".
            'available when Combat equals adversary' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 2, 2);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'tie counts');
            },

            'unavailable when Combat is lower and no Eisenfaust attachment' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 1, 3);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'lower');
            },

            'available when Combat is lower but equipped with Eisenfaust attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 1, 3);
                $this->equipEisenfaust($world, $actor);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'Eisenfaust alt condition');
            },

            'unavailable when Combat is lower and attachment is not Eisenfaust' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 1, 3);
                $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
                $flint->AttachedToId = $actor->Id;
                $actor->Attachments[] = $flint->Id;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'wrong trait');
            },

            'unavailable when adversary is in discard or locker' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world, 3, 1);
                $world->game->forceInDiscardOrLocker = true;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'dead adversary');
            },

            'discount is 1 with Eisenfaust attachment for this combat card' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world, 1, 1);
                $this->equipEisenfaust($world, $actor);

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'discount');
                Assert::count(1, $explanations, 'explained');
            },

            'no discount without Eisenfaust attachment' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world, 1, 1);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'none');
                Assert::count(0, $explanations, 'no explanation');
            },

            // WHY: discount applies only to this card's own Maneuver cost, not other combat cards.
            'no discount when queried for a different combat card' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, 1, 1);
                $this->equipEisenfaust($world, $actor);
                $other = $world->placeCard(new _01054(), Game::LOCATION_HAND, 1);

                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
            },

            'resolve wounds the adversary' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world, 3, 1);

                $event = new EventResolveManeuver();
                $event->maneuverId = $maneuver->Id;
                $event->adversaryId = $foe->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($maneuver->Id, $wounds[0]->abilityId, 'ability');
            },

            'resolve for a different maneuver id does nothing' => function () {
                $world = new TestWorld();
                [, $maneuver, , $foe] = $this->duel($world, 3, 1);

                $event = new EventResolveManeuver();
                $event->maneuverId = 'someOtherManeuver';
                $event->adversaryId = $foe->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}