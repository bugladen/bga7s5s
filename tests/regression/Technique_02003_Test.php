<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_02003_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02003';
    }

    /**
     * Mourad as duel actor with co-located Strega and a Sorcery combat card stubbed.
     *
     * @return array{0:_02003,1:Technique_02003}
     */
    private function duel(TestWorld $world, bool $withStrega = true, bool $withSorcery = true): array
    {
        $mourad = $world->placeCharacter(new _02003(), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->duelActor = $mourad;
        $world->game->globals->set(Game::IN_DUEL, true);

        if ($withStrega) {
            $world->placeCharacter(
                new GenericCharacter('Ally Strega', ['Strega']),
                Game::LOCATION_CITY_DOCKS,
                1
            );
        }

        if ($withSorcery) {
            $world->theah->combatCardsForCurrentRound = [
                new GenericCharacter('Combat Sorcery', ['Sorcery']),
            ];
        }

        /** @var Technique_02003 $technique */
        $technique = $mourad->getTechniques()[0];
        return [$mourad, $technique];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02003(), 'Technique');
            },

            // WHY: draw only when combat card is Sorcery AND you control a Strega at Mourad's location.
            'available in duel with Sorcery combat card and co-located Strega' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable without Strega at location' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, false, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no Strega');
            },

            'unavailable when Strega is at another location' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, false, true);
                $world->placeCharacter(
                    new GenericCharacter('Far Strega', ['Strega']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'far Strega');
            },

            'unavailable without Sorcery combat card' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, true, false);
                $world->theah->combatCardsForCurrentRound = [
                    new GenericCharacter('Plain Combat'),
                ];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no Sorcery');
            },

            'unavailable when actor is not the player\'s' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                Assert::false($technique->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            'resolve queues card draw' => function () {
                $world = new TestWorld();
                [$mourad, $technique] = $this->duel($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'controller');
                Assert::contains($mourad->getInjectCode(), $draws[0]->reason, 'reason');
            },
        ];
    }
}
