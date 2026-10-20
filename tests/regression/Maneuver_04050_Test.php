<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04050;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_04050_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_04050';
    }

    public function tests(): array
    {
        return [
            // WHY: Duelist actor. +1 Thrust even if the adversary is already engaged.
            // Resolve engages the adversary and each real unengaged attachment. A fake
            // and an already engaged attachment are skipped.
            'engages the adversary and their real attachments' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04050(), Game::LOCATION_DUELING_LINE, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $bow->AttachedToId = $foe->Id;
                $engaged = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->AttachedToId = $foe->Id;
                $engaged->Engaged = true;
                $fake = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
                $fake->AttachedToId = $foe->Id;
                $fake->FakeAttachment = true;
                $foe->Attachments = [$bow->Id, $engaged->Id, $fake->Id];
                $foe->Engaged = true;
                $world->theah->duelActor = $duelist;
                $world->theah->duelOpponent = $foe;
                /** @var Maneuver_04050 $maneuver */
                $maneuver = $risk->getManeuvers()[0];
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'still available while they are engaged');

                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $hero;
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $world->theah->duelActor = $duelist;

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = $maneuver->Id;
                $calc->thrust = 3;
                $world->fireOn($risk, $calc);
                Assert::same(4, $calc->thrust, '+1 thrust');

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $world->fireOn($risk, $resolve);
                $engagedIds = array_map(
                    fn($event) => $event->cardId,
                    $world->theah->queuedOfType(EventCardEngaged::class)
                );
                Assert::same([$bow->Id], $engagedIds, 'only the unengaged real attachment');

                $world->theah->takeQueuedEvents();
                $foe->Engaged = false;
                $world->fireOn($risk, $resolve);
                $engagedIds = array_map(
                    fn($event) => $event->cardId,
                    $world->theah->queuedOfType(EventCardEngaged::class)
                );
                Assert::same([$foe->Id, $bow->Id], $engagedIds, 'adversary and attachment');
            },
        ];
    }
}
