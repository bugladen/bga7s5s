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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01085_Test extends TestCase
{
    public function name(): string
    {
        return '_01085 Porté Travel';
    }

    /**
     * Player 1 plays Porté Travel in a duel (actor at Docks vs Foe at Docks) and controls a
     * Sorcerer at the Forum. Threat: actor 2, foe 1.
     *
     * @return array{0:_01085,1:Character,2:Character,3:Character}
     */
    private function duel(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01085(), Game::LOCATION_HAND, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_FORUM, 1);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->theah->duelThreats = [$actor->Id => 2, $foe->Id => 1];
        return [$risk, $actor, $foe, $sorcerer];
    }

    private function calcStats(TestWorld $world, int $combatCardId): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->combatCardId = $combatCardId;
        $event->theah = $world->theah;
        return $event;
    }

    private function choose(TestWorld $world, _01085 $risk, int $id): void
    {
        $risk->actFromCardWithId($world->game, States::DUEL_APPLY_COMBAT_CARD_STATS_01085, 'x', '', $id);
    }

    public function tests(): array
    {
        return [
            'constructs unique dashed Montaigne Sorcery Risk with Action_01085 only' => function () {
                $card = new _01085();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(0, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->DashedRiposte, 'dashed Riposte');
                Assert::true($card->DashedParry, 'dashed Parry');
                Assert::true($card->DashedThrust, 'dashed Thrust');
                Assert::true($card->hasTrait('Sorcery'), 'Sorcery');
                Assert::true($card->hasTrait('Porté'), 'Porté');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::true($card->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01085::class, $card->getActions()[0], 'Action_01085');
            },

            // --- Forced (EventDuelCalculateCombatCardStats) ---

            'Forced: queues transition 01085 when played with a controlled Sorcerer' => function () {
                $world = new TestWorld();
                [$risk] = $this->duel($world);

                $risk->handleEvent($this->calcStats($world, $risk->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01085', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'Forced: a Sorcerer at Player Home still counts' => function () {
                $world = new TestWorld();
                [$risk, , , $sorcerer] = $this->duel($world);
                $sorcerer->Location = Game::LOCATION_PLAYER_HOME;

                $risk->handleEvent($this->calcStats($world, $risk->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'home Sorcerer');
            },

            'Forced: no transition without a Sorcerer' => function () {
                $world = new TestWorld();
                [$risk, , , $sorcerer] = $this->duel($world);
                // WHY: an EMPTY ModifiedTraits is reset to Traits by Card::hasTrait (old-game hack).
                $sorcerer->ModifiedTraits = ['Scoundrel'];

                $risk->handleEvent($this->calcStats($world, $risk->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no Sorcerer');
            },

            'Forced: an opposing Sorcerer does not count' => function () {
                $world = new TestWorld();
                [$risk, , , $sorcerer] = $this->duel($world);
                $sorcerer->ControllerId = 2;

                $risk->handleEvent($this->calcStats($world, $risk->Id));
                Assert::count(0, $world->theah->queuedEvents, 'opponent Sorcerer');
            },

            // WHY: every combat card's stats calc fires this event; only the Porté Travel combat card may trigger.
            'Forced: ignores stats calc for a different combat card' => function () {
                $world = new TestWorld();
                [$risk] = $this->duel($world);

                $risk->handleEvent($this->calcStats($world, $risk->Id + 1));
                Assert::count(0, $world->theah->queuedEvents, 'other card');
            },

            // --- Forced resolution: choose Sorcerer ---

            'args list the controller\'s Sorcerers only' => function () {
                $world = new TestWorld();
                [$risk, , , $sorcerer] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Mundane'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Their Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);

                $args = $risk->argsFromCard($world->game, States::DUEL_APPLY_COMBAT_CARD_STATS_01085, 'x', '');
                Assert::same([$sorcerer->Id], $args['characterIds'], 'own Sorcerers');
            },

            'choosing a Sorcerer wounds them, moves the actor to them and discards all threat' => function () {
                $world = new TestWorld();
                [$risk, $actor, $foe, $sorcerer] = $this->duel($world);

                $this->choose($world, $risk, $sorcerer->Id);

                $queued = $world->theah->queuedEvents;
                Assert::count(3, $queued, 'wound + move + threat');

                Assert::instanceOf(EventCharacterBeingWounded::class, $queued[0], 'wound first');
                Assert::same($sorcerer->Id, $queued[0]->characterId, 'Sorcerer wounded');
                Assert::same(1, $queued[0]->wounds, 'one wound');
                Assert::same($risk->Id, $queued[0]->sourceId, 'source is the card');
                // WHY: abilityId = card Id so ability-sourced gates (Kaspar, Cascade family) see the Forced wound.
                Assert::same((string)$risk->Id, $queued[0]->abilityId, 'ability id tagged');

                Assert::instanceOf(EventCardMoving::class, $queued[1], 'move second');
                Assert::same($actor->Id, $queued[1]->cardId, 'participant moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $queued[1]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $queued[1]->toLocation, 'to Sorcerer');
                Assert::false($queued[1]->engage, 'no engage');

                Assert::instanceOf(EventThreatModified::class, $queued[2], 'threat third');
                Assert::same(-2, $queued[2]->challengerThreat, 'challenger threat zeroed');
                Assert::same(-1, $queued[2]->defenderThreat, 'defender threat zeroed');

                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            'choosing someone not found is rejected' => function () {
                $world = new TestWorld();
                [$risk] = $this->duel($world);

                $threw = false;
                try {
                    $this->choose($world, $risk, 987654);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not found');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'choosing an opposing character is rejected' => function () {
                $world = new TestWorld();
                [$risk, , $foe] = $this->duel($world);

                $threw = false;
                try {
                    $this->choose($world, $risk, $foe->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not yours');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'choosing a non-Sorcerer is rejected' => function () {
                $world = new TestWorld();
                [$risk, $actor] = $this->duel($world);

                $threw = false;
                try {
                    $this->choose($world, $risk, $actor->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not a Sorcerer');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
