<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_02034_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02034';
    }

    /**
     * @return array{0:_02034,1:Technique_02034}
     */
    private function scene(TestWorld $world): array
    {
        $torvo = $world->placeCharacter(new _02034(), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02034 $technique */
        $technique = $torvo->getTechniques()[0];
        return [$torvo, $technique];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02034(), 'Technique');
            },

            'available only during a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside');
            },

            'adds +1 Parry when no Aldana card is in the dueling line' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->riposte = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'parry');
                Assert::same(0, $event->riposte, 'riposte');
                Assert::count(1, $event->explanations, 'explained');
            },

            // WHY: Aldana in Torvo's dueling line swaps the bonus to +1 Riposte.
            'adds +1 Riposte when an Aldana card is in Torvo dueling line' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $aldana = $world->placeCard(new _02039(), Game::LOCATION_DUELING_LINE, 1);
                Assert::true($aldana->hasTrait('Aldana'), 'trait');

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 2;
                $event->riposte = 1;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(2, $event->parry, 'parry unchanged');
                Assert::same(2, $event->riposte, 'riposte');
            },

            'ignores an Aldana card in the opponent dueling line' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $world->placeCard(new _02039(), Game::LOCATION_DUELING_LINE, 2);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->riposte = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'parry');
                Assert::same(0, $event->riposte, 'no riposte');
            },

            'ignores a different technique id' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->parry = 4;
                $event->riposte = 4;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(4, $event->parry, 'parry');
                Assert::same(4, $event->riposte, 'riposte');
                Assert::count(0, $event->explanations, 'silent');
            },
        ];
    }
}
