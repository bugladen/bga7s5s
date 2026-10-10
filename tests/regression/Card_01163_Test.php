<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01162;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01163;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01163_CardClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01163;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class Card_01163_Test extends TestCase
{
    public function name(): string
    {
        return '_01163 Devotion';
    }

    /**
     * Facedown clone at Docks holding a hidden original; parent Risk for inject codes.
     *
     * @return array{0:_01163_CardClone,1:_01163,2:\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card}
     */
    private function facedownAt(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $parent = $world->placeCard(new _01163(), Game::LOCATION_HAND, 1);
        $original = $world->placeCard(new _01162(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
        $clone = $world->placeCard(new _01163_CardClone(), $location, 1);
        $clone->FaceDown = true;
        $clone->ClonedCardId = $original->Id;
        $clone->ParentCardId = $parent->Id;
        $clone->Name = $original->Name;
        return [$clone, $parent, $original];
    }

    public function tests(): array
    {
        return [
            'constructs Faith Risk with Action_01163' => function () {
                $risk = new _01163();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::same(1, $risk->WealthCost, 'cost');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(2, $risk->Parry, 'Parry');
                Assert::same(3, $risk->Thrust, 'Thrust');
                Assert::true($risk->hasTrait('Faith'), 'Faith');
                Assert::instanceOf(Action_01163::class, $risk->getActions()[0], 'Action_01163');
            },

            // WHY (journal 2026-03-31-05): EventDuskPhaseBegin = "end of the day" (same as
            // Yevgeni 01125). Control → hand; otherwise discard. Clone is a facedown placeholder.
            'dusk: controlling the location adds the hidden card to hand' => function () {
                $world = new TestWorld();
                [$clone, , $original] = $this->facedownAt($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($clone, $dusk);

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'clone hidden');
                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $added, 'to hand');
                Assert::same($original->Id, $added[0]->cardId, 'original');
                Assert::same(1, $added[0]->playerId, 'controller');
            },

            'dusk: not controlling the location discards the hidden card' => function () {
                $world = new TestWorld();
                [$clone, , $original] = $this->facedownAt($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($clone, $dusk);

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $clone->Location, 'clone hidden');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'no hand');
                Assert::same(
                    $world->game->getPlayerDiscardDeckName(1),
                    $original->Location,
                    'original discarded'
                );
            },

            'dusk: uncontrolled location (Controller 0) discards the hidden card' => function () {
                $world = new TestWorld();
                [$clone, , $original] = $this->facedownAt($world);
                // Default Controller is 0 — "does not control".

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($clone, $dusk);

                Assert::same(
                    $world->game->getPlayerDiscardDeckName(1),
                    $original->Location,
                    'discarded when uncontrolled'
                );
            },
        ];
    }
}
