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
