<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Technique_01049_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01049';
    }

    private function equipFlint(TestWorld $world, bool $engaged = false): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
        $flint->AttachedToId = $host->Id;
        $flint->Engaged = $engaged;
        $host->Attachments[] = $flint->Id;
        return [$host, $flint];
    }

    public function tests(): array
    {
        return [
            'available in duel when flintlock ready' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Engaged' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world, true);
                $world->game->globals->set(Game::IN_DUEL, true);

                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            // WHY: Engage-as-cost skipped for IsEffectsOnlyCopy / IsTemporaryCopy (Katain/Dame)
            'resolve engages flintlock unless effects-only copy' => function () {
                $world = new TestWorld();
                [, $flint] = $this->equipFlint($world);
                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'engage cost');

                $world->theah->takeQueuedEvents();
                $technique->IsEffectsOnlyCopy = true;
                $technique->handleEvent($event);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'no engage');
            },

            'challenge threat marks Lethal and queues ranged when not preview' => function () {
                $world = new TestWorld();
                [$host, $flint] = $this->equipFlint($world);
                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->preview = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($event->adversaryThreatIsLethal, 'lethal');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
            },

            'challenge threat preview sets Lethal without ranged event' => function () {
                $world = new TestWorld();
                [$host, $flint] = $this->equipFlint($world);
                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->preview = true;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($event->adversaryThreatIsLethal, 'lethal');
                Assert::count(0, $world->theah->queuedEvents, 'no ranged');
            },

            'duel calculate queues GainLethal and ranged' => function () {
                $world = new TestWorld();
                [$host, $flint] = $this->equipFlint($world);
                /** @var Technique_01049 $technique */
                $technique = $flint->getTechniques()[0];

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventThreatModified::class), 'lethal via threat');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged');
            },
        ];
    }
}
