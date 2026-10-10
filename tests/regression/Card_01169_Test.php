<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01169;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class Card_01169_Test extends TestCase
{
    public function name(): string
    {
        return '_01169 Not Today';
    }

    private function combatCardEvent(TestWorld $world, int $combatCardId): EventDuelCalculateCombatCardStats
    {
        $event = new EventDuelCalculateCombatCardStats();
        $event->combatCardId = $combatCardId;
        $event->theah = $world->theah;
        return $event;
    }

    private function endOfRound(TestWorld $world): EventDuelEndOfRound
    {
        $event = new EventDuelEndOfRound();
        $event->theah = $world->theah;
        return $event;
    }

    private function escapeArmed(_01169 $card): bool
    {
        $prop = new \ReflectionProperty(_01169::class, 'EscapeDuel');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($card);
    }

    public function tests(): array
    {
        return [
            'constructs Ad Hoc Risk with dashed Riposte/Thrust and Parry 5' => function () {
                $risk = new _01169();
                Assert::instanceOf(Risk::class, $risk, 'Risk');
                Assert::same(0, $risk->WealthCost, 'WealthCost');
                Assert::same(0, $risk->Riposte, 'Riposte');
                Assert::true($risk->DashedRiposte, 'dashed Riposte');
                Assert::same(5, $risk->Parry, 'Parry');
                Assert::same(0, $risk->Thrust, 'Thrust');
                Assert::true($risk->DashedThrust, 'dashed Thrust');
                Assert::true($risk->hasTrait('Ad Hoc'), 'Ad Hoc');
                // WHY: multi-faction shared Risk — no initializeFaction; Card defaults Neutral.
                Assert::true($risk->hasFaction('Neutral'), 'Neutral');
                Assert::false($this->escapeArmed($risk), 'flag off at construct');
            },

            // WHY: EscapeDuel arms on EventDuelCalculateCombatCardStats for this combat card,
            // then EventDuelEndOfRound wounds + moves Home engaged (Forced on the Risk class).
            'Forced: end of round wounds participant and moves Home engaged after this card was played' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card->Id));
                Assert::true($this->escapeArmed($card), 'armed');
                Assert::true($card->IsUpdated, 'dirty on arm');

                $card->handleEvent($this->endOfRound($world));

                Assert::false($this->escapeArmed($card), 'cleared');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($actor->Id, $wounds[0]->characterId, 'actor');
                Assert::same($card->Id, $wounds[0]->sourceId, 'source');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                // WHY (prod): abilityId = card Id (Joern / Stranahan). Forced on the Risk class.
                Assert::same((string)$card->Id, $wounds[0]->abilityId, 'abilityId is card id');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($actor->Id, $moves[0]->cardId, 'actor');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::true($moves[0]->engage, 'engaged');
                Assert::same($card->Id, $moves[0]->sourceId, 'source');
            },

            'Forced: flag is one-shot and cleared by end of round' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card->Id));
                $card->handleEvent($this->endOfRound($world));
                $world->theah->takeQueuedEvents();
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no re-wound');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no re-move');
            },

            'Forced: end of round without playing this card does nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedEvents, 'no flag');
            },

            'Forced: other combat card id does not arm the flag' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card->Id + 999));
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'wrong card');
            },

            // WHY: characterIsInDiscardOrLocker guard — dead participant skips wound + move.
            'Forced: no wound or move when participant is in discard or locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->forceInDiscardOrLocker = true;

                $card->handleEvent($this->combatCardEvent($world, $card->Id));
                $card->handleEvent($this->endOfRound($world));

                Assert::false($this->escapeArmed($card), 'flag still cleared');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },

            // WHY: Reaction_01109 cancel path calls cancelEscape() so a cancelled Not Today
            // does not still fire wound+Home at end of round.
            'cancelEscape disarms Forced before end of round' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01169(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card->Id));
                Assert::true($this->escapeArmed($card), 'armed');

                $card->cancelEscape();
                Assert::false($this->escapeArmed($card), 'disarmed');
                Assert::true($card->IsUpdated, 'dirty');

                $card->handleEvent($this->endOfRound($world));
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
