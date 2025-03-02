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
    
    public function debug_IncludeCityCard(string $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $this->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, $className);
        }
    }

    public function debug_SetCardInPlayerDiscardPile(int $playerId, string $className)
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

    public function debug_SetCardinCityDiscardPile(string $className)
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

    public function dbgSetCardController(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException("Card not found");

        $card->ControllerId = $playerId;
        $this->updateCardObjectInDb($card);
    }

    public function dbgEngageCard(int $cardId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException("Card not found");

        $card->Engaged = true;
        $this->updateCardObjectInDb($card);
    }

    public function debug_SetDay(int $day)
    {
        $this->setGameStateValue("day", $day);
    }

    public function debug_SetPlayerReknown($playerId, $score)
    {
        $this->DBQuery("UPDATE player SET player_score = $score WHERE player_id = $playerId");
    }

    public function debug_SetRenownAtLocation(string $location, int $amount)
    {
        $this->setReknownForLocation($location, $amount);
    }
}