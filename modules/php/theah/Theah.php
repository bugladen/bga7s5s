<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Theah
{
    public Game $game;
    private array $cards;
    private array $cityLocations;
    private DB $db;
    private bool $cityBuilt;

    use EventHandler;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->cityLocations = [];
        $this->db = new DB();
        $this->cityBuilt = false;
    }

    public function getDBObject()
    {
        return $this->db;
    }

    public function buildCity()
    {
        if ($this->cityBuilt) return;

        $this->cards = [];

        $this->buildCityLocations();

        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PLAYER_HOME);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_OLES_INN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_FORUM);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->game->getActivePlayerId());

        $this->cityBuilt = true;
    }

    private function buildCityLocations()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $game = $this->game;

        $location = new CityLocation(Game::LOCATION_CITY_DOCKS);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_DOCKS);
        $this->cityLocations[Game::LOCATION_CITY_DOCKS] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_FORUM);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_FORUM);
        $this->cityLocations[Game::LOCATION_CITY_FORUM] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_BAZAAR);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cityLocations[Game::LOCATION_CITY_BAZAAR] = $location;

        if (count($players) > 2) {
            $location = new CityLocation(Game::LOCATION_CITY_OLES_INN);
            $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_OLES_INN);
            $this->cityLocations[Game::LOCATION_CITY_OLES_INN] = $location;
        }

        if (count($players) > 3) {
            $location = new CityLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $this->cityLocations[Game::LOCATION_CITY_GOVERNORS_GARDEN] = $location;
        }
    }

    public function getCardPropertiesAtLocation($location, $playerId = null)
    {
        $cards = [];
        foreach ($this->cards as $card) {
            if ($card->Location == $location) {
                if ($playerId !== null && $card->ControllerId != $playerId) {
                    continue;
                }
                $cards[] = $card->getPropertyArray();
                unset($card);
            }
        }
        return $cards;
    }

    public function getCardObjectsAtLocation($location, $playerId = null): array
    {
        return $this->db->getCardObjectsAtLocation($location, $playerId);
    }

    public function getCardById($cardId) : ?Card
    {
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        return null;
    }

    public function getCityLocation(string $name): CityLocation
    {
        return $this->cityLocations[$name];
    }

    public function getCityLocationReknown()
    {
        $reknown = [];
        foreach ($this->cityLocations as $location) {
            $reknown[$location->Name] = $location->Reknown;
        }
        return $reknown;
    }

    public function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    /// <summary>
    /// Run through the cards in the city to see if an event can be run.
    /// A card should throw an exception if it cannot allow the event.
    ///
    /// Call this directly only when you need to queue up several events in one method.
    /// In that case call this for each event before queueing them up.
    /// See examples in ActionsTrait.php
    ///
    /// Otherwise, if you have only one event, call queueEvent() to queue up the event intead, 
    /// which will call this method.
    /// </summary>
    public function eventCheck(Event $event)
    {
        $this->buildCity();
        foreach ($this->cards as $card) {
            $event->theah = $this;
            $card->eventCheck($event);
            unset($event->theah);
        }
    }

    public function queueEvent(Event $event)
    {
        try {
            $this->eventCheck($event);
            $this->db->queueEvent($event);
        } catch (\Exception $e) {
            $this->game->notifyAllPlayers("message", clienttranslate($e->getMessage()), []);
        }
    }

    public function runEvents()
    {
        while (true) {
           
            // Get the next event from the database
            $event = $this->db->getNextEvent();

            // Break if there are no more events
            if (!$event) break;

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

            if ($event instanceof EventTransition) {                
                if($event->getPlayerId()) {
                    $this->game->gamestate->changeActivePlayer($event->getPlayerId());
                }
                $this->game->gamestate->nextState($event->transition);
                return;
            }
        }

        $this->game->gamestate->nextState('endOfEvents');
    }

    function getCharacterCountByPlayerId($playerId)
    {
        $count = 0;
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId) {
                $count++;
            }
        }
        return $count;
    }

    function getCharacterById($id)
    {
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Id == $id) {
                return $card;
            }
        }
        return null;
    }

    function getCharactersByPlayerId($playerId)
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtLocation($location)
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Location == $location) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getLeaderByPlayerId($playerId)
    {
        foreach ($this->cards as $card) {
            if ($card->ControllerId == $playerId && $card instanceof Leader) {
                return $card;
            }
        }
        return null;
    }

    function cardInCity($card): bool
    {
        return $card->Location ==         
        Game::LOCATION_CITY_OLES_INN ||
        Game::LOCATION_CITY_DOCKS ||
        Game::LOCATION_CITY_FORUM ||
        Game::LOCATION_CITY_BAZAAR ||
        Game::LOCATION_CITY_GOVERNORS_GARDEN;
    }

    function getAdjacentCityLocations($location): array
    {
        $playerCount = $this->game->globals->get(Game::PLAYER_COUNT);
        $locations = [];
        switch ($location) {
            case Game::LOCATION_PLAYER_HOME:
                $locations = [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR];
                if ($playerCount > 2) {
                    $locations[] = Game::LOCATION_CITY_OLES_INN;
                }
                if ($playerCount > 3) {
                    $locations[] = Game::LOCATION_CITY_GOVERNORS_GARDEN;
                }
                break;

            case Game::LOCATION_CITY_DOCKS:
                $locations = [Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM];
                if ($playerCount > 2) {
                    $locations[] = Game::LOCATION_CITY_OLES_INN;
                }
                break;

            case Game::LOCATION_CITY_FORUM:
                $locations = [Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR];
                break;

            case Game::LOCATION_CITY_BAZAAR:
                $locations = [Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM];
                if ($playerCount > 3) {
                    $locations[] = Game::LOCATION_CITY_GOVERNORS_GARDEN;
                }
                break;

            case Game::LOCATION_CITY_OLES_INN:
                $locations = [Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS];
                break;

            case Game::LOCATION_CITY_GOVERNORS_GARDEN:
                $locations = [Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_BAZAAR];
                break;
        }

        return $locations;
    }

    public function playerCanMove($playerId): bool
    {
        $characters = $this->getCharactersByPlayerId($playerId);
        $enGardeCharacters = array_filter($characters, function($character) { return $character->Engaged == false; });
        return $enGardeCharacters > 0;
    }

    public function playerCanRecruit($playerId): bool
    {
        $characters = $this->getCharactersByPlayerId($playerId);

        //Get all characters that are in the city that have mercenaries at their location
        $charactersThatCanReruit = [];
        $charactersInCity = array_filter($characters, function($character) { return $character->Location == $this->cardInCity($character); });

        foreach ($charactersInCity as $character) {
            $charactersAtLocation = $this->getCharactersAtLocation($character->Location);
            $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return in_array("Mercenary", $character->Traits); });
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }

        return count($charactersThatCanReruit) > 0;        
    }
}
