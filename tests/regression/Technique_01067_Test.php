<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01067;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01067_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01067';
    }

    /** @return array{0:_01067,1:Technique_01067} */
    private function armed(TestWorld $world): array
    {
        $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Technique_01067 $technique */
        $technique = $jean->getTechniques()[0];
        return [$jean, $technique];
    }

    private function calculate(TestWorld $world, Technique_01067 $technique): EventDuelCalculateTechniqueValues
    {
        $event = new EventDuelCalculateTechniqueValues();
        $event->techniqueId = $technique->Id;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            'available in and out of a duel (also used during a challenge)' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);

                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'outside duel');
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
            },

            'unavailable when Jean is blanked' => function () {
                $world = new TestWorld();
                [$jean, $technique] = $this->armed($world);
                $jean->addCondition(Game::FATES_SILENCE_CONDITION);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'defaults to +1 Thrust' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);

                $event = $this->calculate($world, $technique);

                Assert::same(1, $event->thrust, '+1 Thrust');
                Assert::same(0, $event->riposte, 'no Riposte');
                Assert::count(1, $event->explanations, 'explanation');
            },

            'Riposte choice swaps Thrust for +1 Riposte' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);
                $technique->UseRiposteInstead = true;

                $event = $this->calculate($world, $technique);

                Assert::same(1, $event->riposte, '+1 Riposte');
                Assert::same(0, $event->thrust, 'no Thrust');
            },

            'resolve offers the choice only when another friendly Musketeer is at the location' => function () {
                $world = new TestWorld();
                [$jean, $technique] = $this->armed($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'alone: plain +1 Thrust');

                $world->placeCharacter(new GenericCharacter('Foe Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Far Musketeer', ['Musketeer']), Game::LOCATION_CITY_FORUM, 1);
                $technique->handleEvent($event);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no qualifying Musketeer');

                $world->placeCharacter(new GenericCharacter('Ally Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
                $technique->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'choice offered');
                Assert::same('01067', $transitions[0]->transition, 'transition');
                Assert::same($jean->Id, $transitions[0]->sourceId, 'source Jean');
            },

            'choice 1 selects Thrust' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);
                $technique->UseRiposteInstead = true;

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01067,
                    'duelChooseTechnique_01067',
                    1
                );

                Assert::false($technique->UseRiposteInstead, 'Thrust');
                Assert::same([null], $world->game->gamestate->transitions, 'nextState');
            },

            'choice 2 selects Riposte' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01067,
                    'duelChooseTechnique_01067',
                    2
                );

                Assert::true($technique->UseRiposteInstead, 'Riposte');
                Assert::same([null], $world->game->gamestate->transitions, 'nextState');
            },

            'challenge threat adds 1' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->adversaryThreat, '+1 Threat');
            },

            'challenge threat for another technique is ignored' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(0, $event->adversaryThreat, 'ignored');
            },

            // WHY: the Riposte choice must not leak into the next duel.
            'duel end resets the Riposte choice' => function () {
                $world = new TestWorld();
                [, $technique] = $this->armed($world);
                $technique->UseRiposteInstead = true;

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->UseRiposteInstead, 'reset');
            },
        ];
    }
}
