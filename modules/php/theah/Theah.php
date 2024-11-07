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
        // Get the cards at the all the home locations
        /** @disregard P1012 */
        $location = Game::LOCATION_PLAYER_HOME;
        $homeCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($homeCardsResult as $homeCard) {
            $card = $this->game->getCardObjectFromDb($homeCard['id']);
            $this->cards[] = $card;
        }

        // Get the cards at Ole's Inn
        $location = addslashes(Game::LOCATION_CITY_OLES_INN);
        $oleCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($oleCardsResult as $oleCard) {
            $card = $this->game->getCardObjectFromDb($oleCard['id']);
            $this->cards[] = $card;
        }

        // Get the cards at the Docks
        $location = Game::LOCATION_CITY_DOCKS;
        $dockCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($dockCardsResult as $dockCard) {
            $card = $this->game->getCardObjectFromDb($dockCard['id']);
            $this->cards[] = $card;
        }

        // Get all the cards at the City Forum
        $location = Game::LOCATION_CITY_FORUM;
        $forumCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($forumCardsResult as $forumCard) {
            $card = $this->game->getCardObjectFromDb($forumCard['id']);
            $this->cards[] = $card;
        }

        // Get all the cards at the Grand Bazaar
        $location = Game::LOCATION_CITY_BAZAAR;
        $bazaarCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($bazaarCardsResult as $bazaarCard) {
            $card = $this->game->getCardObjectFromDb($bazaarCard['id']);
            $this->cards[] = $card;
        }

        // Get all the cards at the Governor's Garden
        $location = addslashes(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        $gardenCardsResult = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        foreach ($gardenCardsResult as $gardenCard) {
            $card = $this->game->getCardObjectFromDb($gardenCard['id']);
            $this->cards[] = $card;
        }

        // Get the cards in the approach decks
        $location = Game::LOCATION_APPROACH;
        $approachCards = $this->db->getObjectList("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '$location'
        ");

        foreach ($approachCards as $cardId => $card) {
            $card = $this->game->getCardObjectFromDb($cardId);
            $this->cards[] = $card;
        }
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

}
