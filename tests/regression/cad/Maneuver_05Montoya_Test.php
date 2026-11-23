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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Montoya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Montoya;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Maneuver_05Montoya_Test extends TestCase
{
    public function name(): string
    {
        return 'Maneuver_05Montoya';
    }

    /**
     * @return array{
     *   world: TestWorld,
     *   risk: _05Montoya,
     *   maneuver: Maneuver_05Montoya,
     *   actor: GenericCharacter
     * }
     */
    private function duel(): array
    {
        $world = new TestWorld();
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::FIRST_PLAYER, 2);
        $risk = $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        /** @var Maneuver_05Montoya $maneuver */
        $maneuver = $risk->getManeuvers()[0];

        return compact('world', 'risk', 'maneuver', 'actor');
    }

    public function tests(): array
    {
        return [
            // WHY: Duelist gate + not-first-player discount only for this combat card.
            'needs a Duelist and discounts when you are not first player' => function () {
                ['world' => $world, 'risk' => $risk, 'maneuver' => $maneuver, 'actor' => $actor] = $this->duel();

                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'duelist');
                $actor->addTrait($world->game, 'Hero', true);
                $actor->removeTrait($world->game, 'Duelist');
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'not a duelist');
                $actor->addTrait($world->game, 'Duelist', true);

                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'not first');

                $world->game->globals->set(Game::FIRST_PLAYER, 1);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'is first');

                // Lorenzo override treats first player as not-first for cost.
                $world->game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);
                $explanations = [];
                Assert::same(1, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $risk, $explanations), 'override');

                $other = $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 1);
                $explanations = [];
                Assert::same(0, $maneuver->getManeuverFromCombatCardDiscount($world->theah, $other, $explanations), 'other card');
            },

            // WHY: Count every Flourish in the line including this card — print is not "other".
            'riposte scales with Flourish cards in the dueling line including self' => function () {
                ['world' => $world, 'risk' => $risk, 'maneuver' => $maneuver] = $this->duel();

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(1, $calc->riposte, 'self only');

                $otherFlourish = $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 1);
                $nonFlourish = $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 1);
                // WHY: hasTrait reads ModifiedTraits (reset at construct) — clear Flourish explicitly.
                $nonFlourish->removeTrait($world->game, 'Flourish');
                // Opponent Flourish must not count.
                $world->placeCard(new _05Montoya(), Game::LOCATION_DUELING_LINE, 2);

                $calc = new EventDuelCalculateManeuverValues();
                $calc->theah = $world->theah;
                $calc->maneuverId = $maneuver->Id;
                $maneuver->handleEvent($calc);
                Assert::same(2, $calc->riposte, 'self + one other Flourish');
                unset($risk, $otherFlourish);
            },

            // WHY: Draw always; put-back chooser when hand or deck can supply a card.
            'draws then opens a hand chooser to put a card on top of the deck' => function () {
                ['world' => $world, 'risk' => $risk, 'maneuver' => $maneuver] = $this->duel();

                $resolve = new EventResolveManeuver();
                $resolve->theah = $world->theah;
                $resolve->maneuverId = $maneuver->Id;
                $resolve->playerId = 1;
                $maneuver->handleEvent($resolve);

                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'empty hand+deck skips chooser');

                $handCard = $world->placeCard(new _05Montoya(), Game::LOCATION_HAND, 1);
                $maneuver->handleEvent($resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('05Montoya', $transition->transition, 'chooser');
                Assert::same(1, $transition->playerId, 'owner chooses');

                $world->game->activePlayerId = 1;
                $deckName = $world->game->getPlayerFactionDeckName(1);
                $maneuver->actFromManeuverWithId(
                    $world->game,
                    States::DUEL_RESOLVE_MANEUVER_05MONTOYA,
                    'duelResolveManeuver_05Montoya',
                    $handCard->Id
                );
                Assert::same($deckName, $handCard->Location, 'on top of deck');
                Assert::same([null], array_slice($world->game->gamestate->transitions, -1), 'bare next state');

                $threw = false;
                try {
                    $world->game->activePlayerId = 2;
                    $maneuver->actFromManeuverWithId(
                        $world->game,
                        States::DUEL_RESOLVE_MANEUVER_05MONTOYA,
                        'duelResolveManeuver_05Montoya',
                        $handCard->Id
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not your card / not in hand');
                unset($risk);
            },
        ];
    }
}
