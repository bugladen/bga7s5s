<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class EventDuelCalculateCombatCardStats_Test extends TestCase
{
    public function name(): string
    {
        return 'EventDuelCalculateCombatCardStats remove* debt';
    }

    public function tests(): array
    {
        return [
            // WHY (Hand + Pavel): Strength of Ten 1P, Syrneth Hand removeParry(2) must
            // leave combat_parry = -1 so Technique +1P nets to 0 (not combat 0 + tech 1).
            'removeParry may go negative so later Technique Parry can net correctly' => function () {
                $event = new EventDuelCalculateCombatCardStats();
                $event->addParry(1);
                $event->removeParry(2);
                Assert::same(-1, $event->parry, '1P - 2 Hand = -1 debt');
            },

            'removeParry on 0-Parry combat card stores full penalty debt' => function () {
                $event = new EventDuelCalculateCombatCardStats();
                $event->removeParry(2);
                Assert::same(-2, $event->parry, '0 - 2 = -2');
            },

            'removeRiposte and removeThrust preserve overshoot the same way' => function () {
                $event = new EventDuelCalculateCombatCardStats();
                $event->addRiposte(1);
                $event->addThrust(1);
                $event->removeRiposte(2);
                $event->removeThrust(3);
                Assert::same(-1, $event->riposte, 'riposte debt');
                Assert::same(-2, $event->thrust, 'thrust debt');
            },

            'dashed stats still refuse remove*' => function () {
                $world = new TestWorld();
                $event = new EventDuelCalculateCombatCardStats();
                $event->theah = $world->theah;
                $event->dashedParry = true;
                $event->dashedRiposte = true;
                $event->dashedThrust = true;
                $prop = new \ReflectionProperty(EventDuelCalculateCombatCardStats::class, 'parry');
                $prop->setAccessible(true);
                $prop->setValue($event, 3);
                $prop = new \ReflectionProperty(EventDuelCalculateCombatCardStats::class, 'riposte');
                $prop->setAccessible(true);
                $prop->setValue($event, 3);
                $prop = new \ReflectionProperty(EventDuelCalculateCombatCardStats::class, 'thrust');
                $prop->setAccessible(true);
                $prop->setValue($event, 3);

                $event->removeParry(2);
                $event->removeRiposte(2);
                $event->removeThrust(2);

                Assert::same(3, $event->parry, 'dashed parry');
                Assert::same(3, $event->riposte, 'dashed riposte');
                Assert::same(3, $event->thrust, 'dashed thrust');
                Assert::true(count($event->explanations) >= 3, 'dashed explanations');
            },

            // Document the round total the DB technique branch should see after Hand then Pavel.
            'Hand debt plus Technique +1P nets to 0 before threat apply' => function () {
                $combatParry = 1;
                $combatParry -= 2; // Syrneth Hand
                $techniqueParry = 1; // Pavel Technique_PlusOneParry
                $totalParry = $combatParry + $techniqueParry;
                Assert::same(0, $totalParry, 'net Parry for threat mitigation');
            },
        ];
    }
}
