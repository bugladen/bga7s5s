<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_03036_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_03036';
    }

    /**
     * @return array{0:_03036,1:Maneuver_03036,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $actor->ModifiedFinesse = 3;
        $adversary->ModifiedFinesse = 1;
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_03036 $maneuver */
        $maneuver = $risk->getManeuvers()[0];
        return [$risk, $maneuver, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            // WHY: The -1 is a maneuver-cost discount, only for this card, and only while
            // the participant's Finesse is strictly greater. The maneuver itself needs a Duelist.
            'discounts this card when Finesse is greater, and needs a Duelist' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, $actor, $adversary] = $this->scene($world);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist');
                $actor->addTrait($world->game, 'Hero', true);
                $actor->removeTrait($world->game, 'Duelist');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $actor->addTrait($world->game, 'Duelist', true);

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'discount');
                $adversary->ModifiedFinesse = 3;
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'equal finesse');
                $other = $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'another card');
            },

            // WHY: Riposte is one per other card in this controller's dueling line. Three or
            // more opens the adversary's discard. An empty hand skips that state.
            'riposte scales with the rest of the line, then the adversary discards' => function () {
                $world = new TestWorld();
                [$risk, $maneuver, , $adversary] = $this->scene($world);
                $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 2);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->riposte, 'two other cards of ours');

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'fewer than three, and an empty hand');

                $world->placeCard(new _03036(), Game::LOCATION_DUELING_LINE, 1);
                $hand = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $maneuver->handleEvent($resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03036', $transition->transition, 'discard step');
                Assert::same($adversary->ControllerId, $transition->playerId, 'the adversary chooses');

                $world->game->activePlayerId = 2;
                $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03036, 'x', $hand->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::same($hand->Id, $discard->cardId, 'discarded');
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not a payment');
                Assert::same([null], array_slice($world->game->gamestate->transitions, -1), 'bare next state');

                $threw = false;
                try {
                    $world->game->activePlayerId = 1;
                    $maneuver->actFromManeuverWithId($world->game, States::DUEL_RESOLVE_MANEUVER_03036, 'x', $hand->Id);
                } catch (\BgaUserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not your card');
                unset($risk);
            },
        ];
    }
}
