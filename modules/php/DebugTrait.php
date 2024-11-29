<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait DebugTrait
{
    public function dbgIncludeCityCard($className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $this->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, $className);
        }
    }

    public function dbgSetPlayerReknown($playerId, $score)
    {
        $this->DBQuery("UPDATE player SET player_score = $score WHERE player_id = $playerId");
    }

    public function dbgSetCardInPlayerDiscardPile($playerId, $className)
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

    public function dbgSetCardinCityDiscardPile($className)
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
}