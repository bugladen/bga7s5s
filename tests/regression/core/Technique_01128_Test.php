<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01128;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01128;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_01128_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01128';
    }

    /**
     * Sabre on host; duel live; actor stats configurable.
     *
     * @return array{0:_01128,1:GenericCharacter,2:Technique_01128}
     */
    private function duel(TestWorld $world, int $finesse = 2, int $combat = 3): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $sabre = $world->placeCard(new _01128(), Game::LOCATION_CITY_DOCKS, 1);
        $sabre->AttachedToId = $host->Id;
        $host->Attachments[] = $sabre->Id;
        $host->ModifiedFinesse = $finesse;
        $host->ModifiedCombat = $combat;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01128 $technique */
        $technique = $sabre->getTechniques()[0];
        return [$sabre, $host, $technique];
    }

    public function tests(): array
    {
        return [
            'available in a duel when Finesse is 2 or more' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 2, 0);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'Finesse gate');
            },

            'available in a duel when Combat is 3 or more' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 0, 3);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'Combat gate');
            },

            'unavailable when Finesse and Combat are both below the gates' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 1, 2);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'below both');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            // WHY: Technique::isAvailableToPlayer does not gate on controller — duel UI
            // only offers techniques from the acting participant's cards.
            'in-duel availability is not gated by playerId at the Technique layer' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(2, $world->theah), 'any playerId');
            },

            'calculate adds +1 Parry when Finesse is 2 or more' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 2, 0);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, '+1 Parry');
                Assert::same(0, $event->thrust, 'no Thrust');
                Assert::count(1, $event->explanations, 'explained');
            },

            'calculate adds +1 Thrust when Combat is 3 or more' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 0, 3);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->parry, 'no Parry');
                Assert::same(1, $event->thrust, '+1 Thrust');
            },

            'calculate adds both when both gates are met' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, 2, 3);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'Parry');
                Assert::same(1, $event->thrust, 'Thrust');
                Assert::count(2, $event->explanations, 'both explained');
            },

            'calculate for another technique id is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->parry = 5;
                $event->thrust = 5;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(5, $event->parry, 'untouched');
                Assert::same(5, $event->thrust, 'untouched');
            },
        ];
    }
}
