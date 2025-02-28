<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;

trait DebugTrait
{
    public function debug_AddCardToHand(int $playerId, string $className)
    {
        $card = $this->instantiateCard($className);

        $location = Game::LOCATION_HAND;
        $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', $playerId, '$location', $playerId)";
        $this->DbQuery($sql);

        //Create an instance of the card, set the ID, and save it back into the db
        $id = $this->DbGetLastId();
        $card->setId($id);
        $card->OwnerId = $playerId;
        $card->ControllerId = $playerId;
        $card->Location = $location;
        $this->updateCardObjectInDb($card);

        $this->notifyPlayer($playerId, "drawCard", 'Debug Draw', [
            "card" => $card->getPropertyArray(),
        ]);
    }
    
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

    public function debug_SetDay(int $day)
    {
        $this->setGameStateValue("day", $day);
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