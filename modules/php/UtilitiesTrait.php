<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

trait UtilitiesTrait
{
    protected function instantiateCard($cardId) : Card {

        //Pull the first two characters of the card id to get the set
        $set = substr($cardId, 0, 2);

        switch ($set) {
            case '01':
                $set = "_7s5s";
                break;
            default:
                $set = "_7s5s";
        }

        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\cards\\$set\_$cardId";
        $card = new $className();

        return $card;
    }

    public function getCardObjectFromDb($cardId) : Card {
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

    function getPlayerReknown($player_id) {
        return $this->getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'");
    }

    function setPlayerReknown($playerId, $reknown) {
        $this->DbQuery("UPDATE player SET player_score='$reknown' WHERE player_id=$playerId");
    }

    function dbSetAuxScore($player_id, $score) {
        $this->DbQuery("UPDATE player SET player_score_aux=$score WHERE player_id='$player_id'");
    }

    function incrementReknown($player_id, $inc) {
        $count = $this->getPlayerReknown($player_id);
        if ($inc != 0) {
            $count += $inc;
            $this->setPlayerReknown($player_id, $count);
        }
        return $count;
    }

    function mapCityLocationElementToName($element) {
        switch ($element) {
            case "dock":
                return Game::LOCATION_CITY_DOCKS;
            case "forum":
                return Game::LOCATION_CITY_FORUM;
            case "bazaar":
                return Game::LOCATION_CITY_BAZAAR;
            case "garden":
                return Game::LOCATION_CITY_GOVERNORS_GARDEN;
            case "oles-inn":
                return Game::LOCATION_CITY_OLES_INN;
            default:
                return "";
        }
    }

    function getReknownLocationName($location) {
        return "Reknown_" . $location;
    }

}