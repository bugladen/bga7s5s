<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\CityLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use ReflectionProperty;

/**
 * Theah subclass that keeps world state in memory (no DB rebuild).
 * WHY: Real queueEvent → buildCity() wipes $cards from DB; unit tests seed cards in RAM.
 */
class TestTheah extends Theah
{
    /** @var list<Event> */
    public array $queuedEvents = [];

    public ?Character $duelOpponent = null;
    public ?Character $duelActor = null;
    /** @var array<int, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader> playerId => Leader */
    public array $leadersByPlayerId = [];

    public function __construct(Game $game)
    {
        parent::__construct($game);

        // Prevent first queueEvent from calling buildCity() and clearing seeded cards.
        $cityBuilt = new ReflectionProperty(Theah::class, 'cityBuilt');
        $cityBuilt->setAccessible(true);
        $cityBuilt->setValue($this, true);

        $this->seedDefaultCityLocations();
    }

    public function queueEvent(Event $event): void
    {
        $event->theah = $this;
        $this->queuedEvents[] = $event;
    }

    public function stackEvent(Event $event): void
    {
        $event->theah = $this;
        array_unshift($this->queuedEvents, $event);
    }

    public function eventCheck(Event $event): void
    {
        // Skip DB-backed checks in unit tests.
    }

    public function buildCity(): void
    {
        // no-op — tests manage cards/locations explicitly
    }

    public function getDuelRoundOpponent(): ?Character
    {
        return $this->duelOpponent;
    }

    public function getDuelRoundActor(): ?Character
    {
        return $this->duelActor;
    }

    // WHY: Real getLeaderByPlayerId hits player.leader_card_id in DB (Action_01024 muster).
    public function getLeaderByPlayerId($playerId): ?\Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader
    {
        $leader = $this->leadersByPlayerId[(int)$playerId] ?? null;
        if ($leader instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader) {
            return $leader;
        }
        return null;
    }

    // WHY: Real delete* hits DB; tests keep events in $queuedEvents only.
    public function deleteEventBatch(int $batchId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            fn($event) => $event->batchId !== $batchId
        ));
    }

    public function deleteTransitionEvents(string $reactionId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($reactionId) {
                if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition) {
                    return true;
                }
                return $event->internalId !== $reactionId;
            }
        ));
    }

    // WHY: Real getCardObjectsAtLocation hits DB; hand Thugs for Reaction_01014 live in RAM.
    public function getCardObjectsAtLocation($location, $playerId = null): array
    {
        $cards = [];
        $prop = new ReflectionProperty(Theah::class, 'cards');
        $prop->setAccessible(true);
        /** @var array<int, Card> $map */
        $map = $prop->getValue($this);
        foreach ($map as $card) {
            if ($card->Location !== $location) {
                continue;
            }
            if ($playerId !== null && (int)$card->ControllerId !== (int)$playerId) {
                continue;
            }
            $cards[$card->Id] = $card;
        }
        return $cards;
    }

    public function seedDefaultCityLocations(): void
    {
        $locations = [
            Game::LOCATION_CITY_DOCKS,
            Game::LOCATION_CITY_FORUM,
            Game::LOCATION_CITY_BAZAAR,
            Game::LOCATION_CITY_OLES_INN,
            Game::LOCATION_CITY_GOVERNORS_GARDEN,
        ];

        $cityLocations = [];
        foreach ($locations as $name) {
            $cityLocations[$name] = new CityLocation($name);
        }

        $prop = new ReflectionProperty(Theah::class, 'cityLocations');
        $prop->setAccessible(true);
        $prop->setValue($this, $cityLocations);
    }

    public function setLocationController(string $name, int $controllerId): void
    {
        $this->getCityLocation($name)->Controller = $controllerId;
    }

    public function setLocationRenown(string $name, int $renown): void
    {
        $this->getCityLocation($name)->Renown = $renown;
    }

    /** @return list<Event> */
    public function takeQueuedEvents(): array
    {
        $events = $this->queuedEvents;
        $this->queuedEvents = [];
        return $events;
    }

    /** @return list<Event> */
    public function queuedOfType(string $class): array
    {
        return array_values(array_filter(
            $this->queuedEvents,
            fn($event) => $event instanceof $class
        ));
    }
}
