<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;

class Theah
{
    private Game $game;
    private array $cards;
    private DB $db;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
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
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_APPROACH);
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

    public function getCardById($cardId)
    {
        // make sure cardId is an integer
        $cardId = (int)$cardId;

        // print all the keys in the $cards array
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        return null;
    }

}
