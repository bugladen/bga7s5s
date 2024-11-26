<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Theah
{
    public Game $game;
    private array $cards;
    private array $approachCards;
    private array $factionHand;
    private array $purgatoryCards;
    private array $cityLocations;
    private bool $isLoaded;
    private DB $db;

    use EventHandler;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->cityLocations = [];
        $this->approachCards = [];
        $this->factionHand = [];
        $this->purgatoryCards = [];
        $this->isLoaded = false;
        $this->db = new DB();
    }


    public function buildCity()
    {
        if ($this->isLoaded) {
            return;
        }

        $this->buildCityLocations();

        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PLAYER_HOME);
        $this->cards += $this->db->getCardObjectsAtLocation(addslashes(Game::LOCATION_CITY_OLES_INN));
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_FORUM);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cards += $this->db->getCardObjectsAtLocation(addslashes(Game::LOCATION_CITY_GOVERNORS_GARDEN));

        $this->approachCards = $this->db->getCardObjectsAtLocation(Game::LOCATION_APPROACH);
        $this->factionHand = $this->db->getCardObjectsAtLocation(Game::LOCATION_HAND);
        $this->purgatoryCards = $this->db->getCardObjectsAtLocation(Game::LOCATION_PURGATORY);

        $this->isLoaded = true;
    }

    private function buildCityLocations()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $game = $this->game;

        $location = new CityLocation(Game::LOCATION_CITY_DOCKS);
        $location->Reknown = $game->globals->get($game->getReknownLocationName(Game::LOCATION_CITY_DOCKS));
        $this->cityLocations[Game::LOCATION_CITY_DOCKS] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_FORUM);
        $location->Reknown = $game->globals->get($game->getReknownLocationName(Game::LOCATION_CITY_FORUM));
        $this->cityLocations[Game::LOCATION_CITY_FORUM] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_BAZAAR);
        $location->Reknown = $game->globals->get($game->getReknownLocationName(Game::LOCATION_CITY_BAZAAR));
        $this->cityLocations[Game::LOCATION_CITY_BAZAAR] = $location;

        if (count($players) > 2) {
            $location = new CityLocation(Game::LOCATION_CITY_OLES_INN);
            $location->Reknown = $game->globals->get($game->getReknownLocationName(Game::LOCATION_CITY_OLES_INN));
            $this->cityLocations[Game::LOCATION_CITY_OLES_INN] = $location;
        }

        if (count($players) > 3) {
            $location = new CityLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $location->Reknown = $game->globals->get($game->getReknownLocationName(Game::LOCATION_CITY_GOVERNORS_GARDEN));
            $this->cityLocations[Game::LOCATION_CITY_GOVERNORS_GARDEN] = $location;
        }
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

    public function getfactionHand($playerId)
    {
        $cards = [];
        foreach ($this->factionHand as $card) {
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

    public function getCardById($cardId) : ?Card
    {
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        return null;
    }

    public function getApproachCardById($cardId) : ?Card
    {
        if (array_key_exists($cardId, $this->approachCards)) {
            return $this->approachCards[$cardId];
        }

        return null;
    }

    public function getPurgatoryCardById($cardId) : ?Card
    {
        if (array_key_exists($cardId, $this->purgatoryCards)) {
            return $this->purgatoryCards[$cardId];
        }

        return null;
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

    public function queueEvent(Event $event)
    {
        $this->db->queueEvent($event);
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
            if ($card->ControllerId == $playerId) {
                $count++;
            }
        }
        return $count;
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
}
