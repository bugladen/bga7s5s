<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait DebugTrait
{
    public function debug_IncludeCityCard($className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $this->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, $className);
        }
    }


    public function debug_SetCardInPlayerDiscardPile($playerId, $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $location = $this->getPlayerDiscardDeckName($playerId);
            $dbCard = $this->cards->getCardsOfType($className);
            $dbCard = reset($dbCard);
            if ($dbCard)
                $this->cards->moveCard($dbCard['id'], $location, $playerId);
        }
    }

    public function debug_SetCardinCityDiscardPile($className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $location = Game::LOCATION_CITY_DISCARD;
            $dbCard = $this->cards->getCardsOfType($className);
            $dbCard = reset($dbCard);
            if ($dbCard)
                $this->cards->moveCard($dbCard['id'], $location);
        }
    }

    public function debug_SetPlayerReknown($playerId, $score)
    {
        $this->DBQuery("UPDATE player SET player_score = $score WHERE player_id = $playerId");
    }

    public function debug_SetRenownAtLocation($location, $amount)
    {
        $this->setReknownForLocation($location, $amount);
    }
}