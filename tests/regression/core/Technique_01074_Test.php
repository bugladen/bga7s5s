<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01074;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Technique_01074_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01074 (+1 Riposte)';
    }

    private function equipRapier(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $rapier = $world->placeCard(new _01074(), Game::LOCATION_CITY_DOCKS, 1);
        $rapier->AttachedToId = $host->Id;
        $host->Attachments[] = $rapier->Id;
        return [$host, $rapier];
    }

    public function tests(): array
    {
        return [
            'available only while in a duel' => function () {
                $world = new TestWorld();
                [, $rapier] = $this->equipRapier($world);
                /** @var Technique_PlusOneRiposte $technique */
                $technique = $rapier->getTechniques()[0];

                Assert::false((bool)$technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true((bool)$technique->isAvailableToPlayer(1, $world->theah), 'in duel');
            },

            'calculate adds exactly 1 Riposte and explains it' => function () {
                $world = new TestWorld();
                [, $rapier] = $this->equipRapier($world);
                /** @var Technique_PlusOneRiposte $technique */
                $technique = $rapier->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->riposte = 2;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(3, $event->riposte, 'riposte +1');
                Assert::same(0, $event->parry, 'parry untouched');
                Assert::same(0, $event->thrust, 'thrust untouched');
                Assert::count(1, $event->explanations, 'explanation');
            },

            // WHY: Two +1 Riposte techniques can be in a duel; only the activated one may contribute.
            'calculate for a different technique id adds nothing' => function () {
                $world = new TestWorld();
                [, $rapier] = $this->equipRapier($world);
                /** @var Technique_PlusOneRiposte $technique */
                $technique = $rapier->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'someOther_Technique_01074';
                $event->riposte = 2;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->riposte, 'unchanged');
            },

            'two rapiers keep independent technique ids' => function () {
                $world = new TestWorld();
                [, $rapierA] = $this->equipRapier($world);
                $rapierB = $world->placeCard(new _01074(), Game::LOCATION_CITY_DOCKS, 1);
                $techA = $rapierA->getTechniques()[0];
                $techB = $rapierB->getTechniques()[0];
                Assert::true($techA->Id !== $techB->Id, 'distinct ids');

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $techA->Id;
                $event->theah = $world->theah;
                $techA->handleEvent($event);
                $techB->handleEvent($event);

                Assert::same(1, $event->riposte, 'only A applied');
            },

            'activation marks Used and duel end resets it' => function () {
                $world = new TestWorld();
                [, $rapier] = $this->equipRapier($world);
                /** @var Technique_PlusOneRiposte $technique */
                $technique = $rapier->getTechniques()[0];

                $activated = new EventTechniqueActivated();
                $activated->techniqueId = $technique->Id;
                $activated->theah = $world->theah;
                $technique->handleEvent($activated);
                Assert::true($technique->Used, 'used');

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                Assert::false($technique->Used, 'reset on duel end');
            },
        ];
    }
}
