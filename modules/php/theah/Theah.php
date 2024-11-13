<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class Theah
{
    public Game $game;
    private array $cards;
    private array $approachCards;
    private array $purgatoryCards;
    private array $events;
    private DB $db;

    use EventHandler;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->approachCards = [];
        $this->purgatoryCards = [];
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
        $this->purgatoryCards = $this->db->getCardObjectsAtLocation(Game::LOCATION_PURGATORY);
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
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        return null;
    }

    public function getApproachCardById($cardId) : Card
    {
        if (array_key_exists($cardId, $this->approachCards)) {
            return $this->approachCards[$cardId];
        }

        return null;
    }

    public function getPurgatoryCardById($cardId) : Card
    {
        if (array_key_exists($cardId, $this->purgatoryCards)) {
            return $this->purgatoryCards[$cardId];
        }

        return null;
    }

    public function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    public function queueEvent(Event $event)
    {
        $this->db->queueEvent($event);
    }

    public function runQueuedEvents()
    {
        while (true) {
           
            // Get the next event from the database
            $event = $this->db->getNextEvent();

            // Break if there are no more events
            if (!$event) break;

            $this->game->debug("*******event". get_class($event));

            // Run the event for Theah
            $event->theah = $this;
            $this->handleEvent($event);

            //Run the event for all cards in play
            foreach ($this->cards as $card) 
            {
                $card->handleEvent($event);
            }

            // If any cards were updated, update them in the database
            foreach ($this->cards as $card) {
                if ($card->IsUpdated) {
                    $card->IsUpdated = false;
                    $this->db->updateCardObject($card);
                }
            }

            if ( ! empty($event->transition)) {
                $this->game->gamestate->nextState($event->transition);
                break;
            }
        }
    }
}
