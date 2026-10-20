<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01093_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01093';
    }

    /**
     * Maya (P1) duels Foe (P2) at Docks; her combat card has 1 Riposte; P2 is the active (discarding) player.
     *
     * @return array{0:_01093,1:Character,2:Technique_01093}
     */
    private function duel(TestWorld $world): array
    {
        $maya = $world->placeCharacter(new _01093(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $maya;
        $world->theah->duelOpponent = $foe;
        $world->theah->currentRoundRiposte = 1;
        $world->game->activePlayerId = 2;
        /** @var Technique_01093 $technique */
        $technique = $maya->getTechniques()[0];
        return [$maya, $foe, $technique];
    }

    private function resolve(TestWorld $world, Technique_01093 $technique): EventResolveTechnique
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'available in a duel when her combat card has Riposte' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: "(Your combat card must have at least 1 Riposte.)" - the -1 would otherwise underflow.
            'unavailable when the round has no Riposte' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->theah->currentRoundRiposte = 0;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no Riposte');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no duel');
            },

            'unavailable when Fate\'s Silence blanks Maya' => function () {
                $world = new TestWorld();
                [$maya, , $technique] = $this->duel($world);
                $maya->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'calculate subtracts 1 Riposte with an explanation' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;

                $technique->handleEvent($event);

                Assert::same(-1, $event->riposte, '-1 Riposte');
                Assert::same(0, $event->parry, 'no Parry');
                Assert::same(0, $event->thrust, 'no Thrust');
                Assert::count(1, $event->explanations, 'explanation');
            },

            'calculate for another technique is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;

                $technique->handleEvent($event);

                Assert::same(0, $event->riposte, 'ignored');
            },

            'resolve with a card in the adversary hand queues the discard prompt for the adversary' => function () {
                $world = new TestWorld();
                [$maya, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);

                $technique->handleEvent($this->resolve($world, $technique));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'prompt');
                Assert::same('01093', $transitions[0]->transition, 'name');
                Assert::same(2, $transitions[0]->playerId, 'adversary chooses');
                Assert::same($maya->Id, $transitions[0]->sourceId, 'source Maya');
                Assert::same($technique->Id, $transitions[0]->internalId, 'technique id');
                Assert::true($technique->Used, 'used');
            },

            // WHY (journal 2026-04-09-12 bug 5): the discard state has no skip button - an empty hand would deadlock it.
            'resolve with an empty adversary hand skips the prompt but still uses the technique' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $technique->handleEvent($this->resolve($world, $technique));

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no prompt');
                Assert::true($technique->Used, 'still used');
            },

            'only the adversary hand counts, not Maya\'s own' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('My Card'), Game::LOCATION_HAND, 1);

                $technique->handleEvent($this->resolve($world, $technique));

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'adversary hand empty');
            },

            'resolve for another technique id is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);
                $event = $this->resolve($world, $technique);
                $event->techniqueId = 'someOtherTechnique';

                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
                Assert::false($technique->Used, 'not used');
            },

            'discard choice queues an effect discard sourced from Maya and advances' => function () {
                $world = new TestWorld();
                [$maya, , $technique] = $this->duel($world);
                $card = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'duelChooseTechnique_01093', $card->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'one discard');
                Assert::same($card->Id, $discards[0]->cardId, 'card');
                Assert::same(2, $discards[0]->ownerId, 'owner is the adversary');
                Assert::same($maya->Id, $discards[0]->sourceId, 'source Maya');
                Assert::false($discards[0]->AsPayment, 'not payment');
                Assert::true($discards[0]->asEffect, 'effect');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'discard choice refuses an unknown card' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'x', 999999);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not found');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'discard choice refuses a card the active player does not control' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $mine = $world->placeCharacter(new GenericCharacter('Maya\'s Card'), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'x', $mine->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not controlled');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'discard choice refuses a card that is not in hand' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $inPlay = $world->placeCharacter(new GenericCharacter('In Play'), Game::LOCATION_CITY_FORUM, 2);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'x', $inPlay->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'act for an unrelated state does nothing' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $card = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01090, 'x', $card->Id);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
            },
        ];
    }
}
