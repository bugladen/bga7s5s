<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02011_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02011';
    }

    /**
     * Katain in duel with unengaged Ranged Weapon attached.
     *
     * @return array{0:_02011,1:Technique_02011,2:_01049}
     */
    private function duel(TestWorld $world, bool $engaged = false): array
    {
        $katain = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 1);
        $flint->AttachedToId = $katain->Id;
        $flint->Engaged = $engaged;
        $katain->Attachments[] = $flint->Id;

        $world->theah->duelActor = $katain;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var Technique_02011 $technique */
        $technique = $katain->getTechniques()[0];
        return [$katain, $technique, $flint];
    }

    public function tests(): array
    {
        return [
            'available in duel with unengaged Ranged Weapon' => function () {
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

            'unavailable without a Ranged Weapon' => function () {
                $world = new TestWorld();
                $katain = $world->placeCharacter(new _02011(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $katain;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_02011 $technique */
                $technique = $katain->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no ranged');
            },

            // WHY: must engage an unengaged Ranged Weapon — already engaged fails the gate.
            'unavailable when Ranged Weapon is Engaged' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'resolve queues transition 02011' => function () {
                $world = new TestWorld();
                [$katain, $technique] = $this->duel($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02011', $transitions[0]->transition, 'name');
                Assert::same($katain->Id, $transitions[0]->sourceId, 'source');
            },

            'duel calc adds +1 Parry' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'parry');
                Assert::count(1, $event->explanations, 'explained');
            },

            'act engages chosen Ranged Weapon' => function () {
                $world = new TestWorld();
                [, $technique, $flint] = $this->duel($world);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02011,
                    'duelChooseTechnique_02011',
                    $flint->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($flint->Id, $engages[0]->cardId, 'flint');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'act rejects attachment not equipped to actor' => function () {
                $world = new TestWorld();
                [, $technique] = $this->duel($world);
                $loose = $world->placeCard(new _01049(), Game::LOCATION_CITY_FORUM, 1);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02011,
                        'x',
                        $loose->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not equipped');
            },

            'act rejects engaged Ranged Weapon' => function () {
                $world = new TestWorld();
                [, $technique, $flint] = $this->duel($world);
                $flint->Engaged = true;

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02011,
                        'x',
                        $flint->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'engaged');
            },
        ];
    }
}
