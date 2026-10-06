<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01022;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01022_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01022';
    }

    private function attachStiletto(TestWorld $world, int $hostController = 1): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, $hostController);
        $stiletto = $world->placeCard(new _01022(), Game::LOCATION_CITY_DOCKS, $hostController);
        $stiletto->AttachedToId = $host->Id;
        $stiletto->Engaged = false;
        return [$host, $stiletto];
    }

    public function tests(): array
    {
        return [
            'offers when challenge issued at attachment location and Stiletto ready' => function () {
                $world = new TestWorld();
                [$host, $stiletto] = $this->attachStiletto($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $defender->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($stiletto->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY regression: journal 2026-09-29 — engage-this-card is a cost; Engaged cannot pay
            'does not offer when Stiletto already Engaged' => function () {
                $world = new TestWorld();
                [$host, $stiletto] = $this->attachStiletto($world);
                $stiletto->Engaged = true;
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'Engaged blocks');
            },

            'does not offer when challenge is at a different location' => function () {
                $world = new TestWorld();
                [$host, $stiletto] = $this->attachStiletto($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 2);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'wrong location');
            },

            'does not offer when not attached' => function () {
                $world = new TestWorld();
                $stiletto = $world->placeCard(new _01022(), Game::LOCATION_HAND, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not attached');
            },

            'woundChallenger engages Stiletto and wounds challenger' => function () {
                $world = new TestWorld();
                [$host, $stiletto] = $this->attachStiletto($world);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundChallenger');

                Assert::same($stiletto->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');
                Assert::same($challenger->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'woundChallenged wounds defender' => function () {
                $world = new TestWorld();
                [$host, $stiletto] = $this->attachStiletto($world);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_TARGET, $defender->Id);

                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundChallenged');

                Assert::same($defender->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound defender');
            },

            'buttons include Wound Challenger, Wound Challenged, Pass' => function () {
                $world = new TestWorld();
                [, $stiletto] = $this->attachStiletto($world);
                /** @var Reaction_01022 $reaction */
                $reaction = $stiletto->getReactions()[0];
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('woundChallenger', $ids, true), 'challenger');
                Assert::true(in_array('woundChallenged', $ids, true), 'challenged');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },
        ];
    }
}
