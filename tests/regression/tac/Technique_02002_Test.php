<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02002;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

class Technique_02002_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02002';
    }

    /**
     * @return array{0:_02002,1:Technique_02002}
     */
    private function scene(TestWorld $world): array
    {
        $eli = $world->placeCharacter(new _02002(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Technique_02002 $technique */
        $technique = $eli->getTechniques()[0];
        return [$eli, $technique];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02002(), 'Technique');
            },

            // WHY: printed Sorcerer Technique is -1 Thrust (duel calc) and -1 adversary
            // threat when chosen as the challenge technique.
            'challenge threat subtracts 1 adversary threat' => function () {
                $world = new TestWorld();
                [$eli, $technique] = $this->scene($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->adversaryThreat = 3;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->adversaryThreat, '-1 threat');
                Assert::count(1, $event->explanations, 'explained');
                Assert::contains($eli->getInjectCode(), $event->explanations[0], 'owner');
            },

            'duel calculate subtracts 1 Thrust' => function () {
                $world = new TestWorld();
                [$eli, $technique] = $this->scene($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->thrust = 4;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(3, $event->thrust, '-1 Thrust');
                Assert::count(1, $event->explanations, 'explained');
                Assert::contains($eli->getInjectCode(), $event->explanations[0], 'owner');
            },

            'ignores other technique ids' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->thrust = 4;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(4, $event->thrust, 'unchanged');
                Assert::count(0, $event->explanations, 'no explain');
            },
        ];
    }
}
