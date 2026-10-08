<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01061;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;

class Maneuver_01061_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01061';
    }

    private function attach(TestWorld $world, Character $host, Card $attachment): void
    {
        $placed = $world->placeCard($attachment, $host->Location, $host->ControllerId);
        $placed->AttachedToId = $host->Id;
        $host->Attachments[] = $placed->Id;
    }

    /** @return array{0:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk,1:Maneuver_01061,2:Character,3:Character} */
    private function scenario(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01061(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01061 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function calc(TestWorld $world, Maneuver_01061 $maneuver, Character $actor, Character $foe, int $riposte = 1): EventDuelCalculateManeuverValues
    {
        $calc = new EventDuelCalculateManeuverValues();
        $calc->maneuverId = $maneuver->Id;
        $calc->actorId = $actor->Id;
        $calc->adversaryId = $foe->Id;
        $calc->riposte = $riposte;
        $calc->parry = 0;
        $calc->thrust = 0;
        $calc->explanations = [];
        $calc->theah = $world->theah;
        $maneuver->handleEvent($calc);
        return $calc;
    }

    public function tests(): array
    {
        return [
            'calc adds +1 Riposte and does not draw without a Weapon' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->scenario($world);

                $calc = $this->calc($world, $maneuver, $actor, $foe, 1);
                Assert::same(2, $calc->riposte, '+1 Riposte');
                Assert::same(0, $calc->thrust, 'thrust untouched');
                Assert::count(1, $calc->explanations, 'explanation recorded');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
            },

            'calc draws a card when participant is equipped with a Weapon' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $foe] = $this->scenario($world);
                $this->attach($world, $actor, new _01048());

                $calc = $this->calc($world, $maneuver, $actor, $foe, 1);
                Assert::same(2, $calc->riposte, '+1 Riposte');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'owner draws');
            },

            // WHY: only a Weapon-trait attachment triggers the draw; _01050 (Unsavory Salve) is a non-Weapon FactionAttachment.
            'calc does not draw for a non-Weapon attachment' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->scenario($world);
                $this->attach($world, $actor, new _01050());

                $calc = $this->calc($world, $maneuver, $actor, $foe, 0);
                Assert::same(1, $calc->riposte, '+1 Riposte still applies');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
            },

            'only the actor equipment counts, not the adversary' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->scenario($world);
                $this->attach($world, $foe, new _01048());

                $this->calc($world, $maneuver, $actor, $foe);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'adversary weapon ignored');
            },

            'calc ignores other maneuver ids' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor, $foe] = $this->scenario($world);
                $this->attach($world, $actor, new _01048());

                $calc = new EventDuelCalculateManeuverValues();
                $calc->maneuverId = 'other';
                $calc->actorId = $actor->Id;
                $calc->adversaryId = $foe->Id;
                $calc->riposte = 1;
                $calc->explanations = [];
                $calc->theah = $world->theah;
                $maneuver->handleEvent($calc);

                Assert::same(1, $calc->riposte, 'unchanged');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
