<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;

class Theah
{
    private Game $game;
    private array $cards;
    private array $approachCards;
    private array $events;
    private DB $db;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->approachCards = [];
        $this->events = [];
        $this->db = new DB();
    }


    public function buildCity()
    {
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PLAYER_HOME);
        $this->cards += $this->db->getCardObjectsAtLocation(addslashes(Game::LOCATION_CITY_OLES_INN));
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_FORUM);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cards += $this->db->getCardObjectsAtLocation(addslashes(Game::LOCATION_CITY_GOVERNORS_GARDEN));

        $this->approachCards = $this->db->getCardObjectsAtLocation(Game::LOCATION_APPROACH);
    }

    public function getApproachCards($playerId)
    {
        $cards = [];
        foreach ($this->approachCards as $card) {
            if ($card->ControllerId != $playerId) {
                continue;
            }
            $cards[] = $card->getPropertyArray();
        }
        return $cards;
    }

    public function getCardsAtLocation($location, $playerId = null)
    {
        $cards = [];
        foreach ($this->cards as $card) {
            if ($card->Location == $location) {
                if ($playerId !== null && $card->ControllerId != $playerId) {
                    continue;
                }
                $cards[] = $card->getPropertyArray();
            }
        }
        return $cards;
    }

    public function getCardById($cardId) : Card
    {
        // make sure cardId is an integer
        $cardId = (int)$cardId;

        // print all the keys in the $cards array
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        return null;
    }

    public function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className($this);
        return $event;
    }

    public function queueEvent(Event $event)
    {
        $this->events[] = $event;
    }

    private function runEvents(array $events)
    {
        $newEvents = [];
        //For each event
        foreach ($events as $event) 
        {
            //Run the event for all cards in play
            foreach ($this->cards as $card) 
            {
                $event = $card->handleEvent($event);

                // Merge the new events with the existing new events
                $newEvents += $event->getNewEvents();
            }
        }

        if (count($newEvents) > 0) {
            $this->runEvents($newEvents);
        }
    }

    public function runQueuedEvents()
    {
        $this->runEvents($this->events);

        // If any cards were updated, update them in the database
        foreach ($this->cards as $card) {
            if ($card->IsUpdated) {
                $card->IsUpdated = false;
                $this->db->updateCardObject($card);
            }
        }

        //Clear the events array
        $this->events = [];
    }
}
