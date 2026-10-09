<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Maneuver_01114_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_01114';
    }

    /** @return array{0:_01114,1:Maneuver_01114,2:Character,3:Character} */
    private function duel(TestWorld $world, array $actorTraits = []): array
    {
        $risk = $world->placeCard(new _01114(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor', $actorTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        /** @var Maneuver_01114 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $foe];
    }

    private function resolve(TestWorld $world, Maneuver_01114 $maneuver, Character $foe): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuver->Id;
        $event->adversaryId = $foe->Id;
        $event->playerId = 1;
        $event->theah = $world->theah;
        $maneuver->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available during a duel (no extra gate)' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: resolve stamps GAMBLE_TYPE + ROLL_THE_BONES_CARD_ID before reveal count so the
            // Scoundrel +1 cannot leak across rounds via an instance flag.
            'resolve sets Roll-the-Bones gamble globals and reveal count' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world);

                $this->resolve($world, $maneuver, $foe);

                Assert::same(Game::GAMBLE_TYPE_ROLL_THE_DICE, $world->game->globals->get(Game::GAMBLE_TYPE), 'type');
                Assert::same($risk->Id, $world->game->globals->get(Game::ROLL_THE_BONES_CARD_ID), 'card id');
                Assert::true($world->game->globals->get(Game::ROLL_THE_BONES_ACTIVATED) === true, 'activated');
                Assert::same(2, $world->game->globals->get(Game::GAMBLE_REVEAL_COUNT), 'base reveal 2');
            },

            'resolve with a Scoundrel actor reveals one extra card' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $foe] = $this->duel($world, ['Scoundrel']);

                $this->resolve($world, $maneuver, $foe);

                Assert::same(3, $world->game->globals->get(Game::GAMBLE_REVEAL_COUNT), '2+1 Scoundrel');
                Assert::contains($risk->getInjectCode(), (string)$world->game->globals->get(Game::GAMBLE_REVEAL_EXPLANATIONS), 'explained');
            },

            'getNumberOfGambleCardsToReveal only bumps while Roll-the-Bones globals match this card' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor] = $this->duel($world, ['Scoundrel']);
                $world->game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $world->game->globals->set(Game::ROLL_THE_BONES_CARD_ID, $risk->Id);

                $explanations = [];
                Assert::same(1, $maneuver->getNumberOfGambleCardsToReveal($world->theah, $actor, $explanations), '+1');
                Assert::count(1, $explanations, 'explained');

                $world->game->globals->set(Game::ROLL_THE_BONES_CARD_ID, 999);
                $explanations = [];
                Assert::same(0, $maneuver->getNumberOfGambleCardsToReveal($world->theah, $actor, $explanations), 'wrong card');
            },

            'no Scoundrel bonus under normal gamble type' => function () {
                $world = new TestWorld();
                [, $maneuver, $actor] = $this->duel($world, ['Scoundrel']);
                $world->game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_NORMAL);

                $explanations = [];
                Assert::same(0, $maneuver->getNumberOfGambleCardsToReveal($world->theah, $actor, $explanations), 'normal');
            },

            'cancel clears Roll-the-Bones globals when they point at this Risk' => function () {
                $world = new TestWorld();
                [$risk, $maneuver] = $this->duel($world);
                $world->game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $world->game->globals->set(Game::ROLL_THE_BONES_CARD_ID, $risk->Id);
                $world->game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);

                $event = new EventManeuverCanceled();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(null, $world->game->globals->get(Game::ROLL_THE_BONES_CARD_ID), 'card cleared');
                Assert::same(null, $world->game->globals->get(Game::ROLL_THE_BONES_ACTIVATED), 'activated cleared');
                Assert::same(Game::GAMBLE_TYPE_NORMAL, $world->game->globals->get(Game::GAMBLE_TYPE), 'type reset');
            },

            'cancel leaves other cards\' Roll-the-Bones globals alone' => function () {
                $world = new TestWorld();
                [, $maneuver] = $this->duel($world);
                $world->game->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_ROLL_THE_DICE);
                $world->game->globals->set(Game::ROLL_THE_BONES_CARD_ID, 999);
                $world->game->globals->set(Game::ROLL_THE_BONES_ACTIVATED, true);

                $event = new EventManeuverCanceled();
                $event->maneuverId = $maneuver->Id;
                $event->theah = $world->theah;
                $maneuver->handleEvent($event);

                Assert::same(999, $world->game->globals->get(Game::ROLL_THE_BONES_CARD_ID), 'untouched');
                Assert::same(Game::GAMBLE_TYPE_ROLL_THE_DICE, $world->game->globals->get(Game::GAMBLE_TYPE), 'type untouched');
            },
        ];
    }
}
