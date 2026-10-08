<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class Card_01053_Test extends TestCase
{
    public function name(): string
    {
        return '_01053 Hexenjagd';
    }

    private function combatCardEvent(TestWorld $world, _01053 $card, int $combatCardId): EventDuelCalculateCombatCardStats
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

    public function tests(): array
    {
        return [
            'constructs Risk with Reaction_01053 only' => function () {
                $card = new _01053();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::false($card instanceof IHasManeuvers, 'no maneuvers');
                Assert::same(0, $card->WealthCost, 'WealthCost');
                Assert::same(0, $card->Riposte, 'Riposte');
                Assert::same(3, $card->Parry, 'Parry');
                Assert::same(0, $card->Thrust, 'Thrust');
                Assert::true($card->hasTrait('Hunt'), 'Hunt');
                Assert::true($card->hasTrait('Zeal'), 'Zeal');
                Assert::true($card->hasFaction('Eisen'), 'Eisen');
                Assert::instanceOf(Reaction_01053::class, $card->getReactions()[0], 'Reaction_01053');
            },

            'Forced: end of round moves participant Home engaged after this card was played' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card, $card->Id));
                $card->handleEvent($this->endOfRound($world));

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move queued');
                Assert::same($actor->Id, $moves[0]->cardId, 'actor moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to Home');
                Assert::true($moves[0]->engage, 'engaged');
                Assert::same($card->Id, $moves[0]->sourceId, 'source is Hexenjagd');
            },

            // WHY: card text "unless the adversary is a Sorcerer" — Sorcerer adversary keeps the participant in place.
            'Forced: no move when adversary is a Sorcerer' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $sorcerer;

                $card->handleEvent($this->combatCardEvent($world, $card, $card->Id));
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },

            'Forced: flag is one-shot and cleared by end of round' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card, $card->Id));
                $card->handleEvent($this->endOfRound($world));
                $world->theah->takeQueuedEvents();
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'second round does not re-move');
            },

            'Forced: end of round without playing this card does nothing' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no flag');
            },

            'Forced: other combat card id does not arm the flag' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;

                $card->handleEvent($this->combatCardEvent($world, $card, $card->Id + 999));
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'wrong combat card');
            },

            'Forced: no move when participant is in discard or locker' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _01053(), Game::LOCATION_HAND, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->forceInDiscardOrLocker = true;

                $card->handleEvent($this->combatCardEvent($world, $card, $card->Id));
                $card->handleEvent($this->endOfRound($world));

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'dead participant not moved');
            },
        ];
    }
}
