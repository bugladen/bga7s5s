<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

trait DeckTrait
{
    public function buildDecks() {

        // *** Create the city deck ***

        require('includes/citydecks.inc.php');

        // Load the city deck JSON
        $city_decks = json_decode($this->city_decks);

        // TODO: City deck loaded should be based on option
        // Pull the city deck with id of '7s5s'
        $city = current(array_filter($city_decks->decks, 
            function($deck) {
                return $deck->id === '7s5s';
            }
        ));

        foreach ($city->cards as $cityCard) {
            $location = Game::LOCATION_CITY_DECK;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$cityCard}', 0, '{$location}', 0)";
            $this->DbQuery($sql);

            //Store the card Id in the object, and serialize the card object to the db
            $id = $this->DbGetLastId();
            $card = $this->instantiateCard($cityCard);
            $card->setId($id);
            $this->updateCardObjectInDb($card);
        }
        $this->cards->shuffle(Game::LOCATION_CITY_DECK);

        // Load the decks selected by the players
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $playerId => $player ) 
        {
            // Get the source and deck_id of the deck from the DB for the  player
            $result = $this->getObjectFromDB("SELECT deck_source FROM player WHERE player_id = '$playerId'");
            $deck = json_decode($result['deck_source']);
            
            //Now that we have a deck, add the cards in the deck to the db

            // Leader
            $location = Game::LOCATION_PLAYER_HOME;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$deck->leader}', $playerId, '{$location}', $playerId)";
            $this->DbQuery($sql);
            $id = $this->DbGetLastId();

            //Instantiate the leader card and assign it the id from the db
            $card = $this->instantiateCard($deck->leader);
            $card->OwnerId = $playerId;
            $card->ControllerId = $playerId;
            if ($card instanceof Leader) {
                $card->setId($id);
                $card->Location = $location;
                $this->updateCardObjectInDb($card);

                //Set the id of the leader card in the player record
                $sql = "UPDATE player SET leader_card_id = $id WHERE player_id = $playerId";
                $this->DbQuery($sql);

                //Notify players about the leaders
                $this->notifyAllPlayers("playLeader", clienttranslate('${player_name} plays ${player_faction} Faction and ${leader_name} as their leader.'), [
                    'i18n' => ['player_faction', 'leader_name'],
                    "player_name" => $player['player_name'],
                    "player_faction" => "<span style='font-weight:bold'>{$card->Faction}</span>",
                    "leader_name" => "<span style='font-weight:bold'>{$card->Name}</span>",
                    "player_id" => $playerId,
                    "player_color" => $player['player_color'],
                    "leader" => $card->getPropertyArray($this),
                ]);
            }

            // *** Create the approach deck and send each card to the player ***
            $approachDeck = $deck->approach_deck;
            $cards = [];
            $location = GAME::LOCATION_APPROACH;
            foreach ($approachDeck as $approachCard) {
                $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$approachCard}', $playerId, '$location', $playerId)";
                $this->DbQuery($sql);

                //Create an instance of the card, set the ID, and save it back into the db
                $id = $this->DbGetLastId();
                $card = $this->instantiateCard($approachCard);
                $card->setId($id);
                $card->OwnerId = $playerId;
                $card->ControllerId = $playerId;
                $card->Location = $location;
                $this->updateCardObjectInDb($card);

                $cards[] = $card->getPropertyArray($this);
            }

            $cardList = implode(", ", array_map(function($card) { return self::_($card['name']); }, $cards));
            $this->notifyPlayer($playerId, "approachCardsReceived", 
                clienttranslate('Private:You received your Approach Deck containing: ${card_list}'), [
                    "card_list" => $cardList,
                    "cards" => $cards
                ]);

            // Create player's Faction deck
            $factionDeck = $deck->faction_deck;
            $cards = [];
            $location = $this->getPlayerFactionDeckName($playerId);
            foreach ($factionDeck as $factionCard) {
                for ($i = 0; $i < $factionCard->count; $i++) {
                    $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$factionCard->id}', $playerId, '$location', $playerId)";
                    $this->DbQuery($sql);

                    //Create an instance of the card, set the ID, and save it back into the db
                    $id = $this->DbGetLastId();
                    $card = $this->instantiateCard($factionCard->id);
                    $card->setId($id);
                    $card->OwnerId = $playerId;
                    $card->ControllerId = $playerId;
                    $card->Location = $location;
                    $this->updateCardObjectInDb($card);

                    $cards[] = $card->getPropertyArray($this);

                }
            }
            $this->cards->shuffle($location, $playerId);
        }
    }

    public function getCardPropertiesInLocation($location, $playerId = null)
    {
        $cards = [];
        $locationCards = $this->cards->getCardsInLocation($location);
        foreach ($locationCards as $cardId) {
            $card = $this->getCardObjectFromDb($cardId['id']);
            if ($playerId !== null && $card->OwnerId != $playerId)
            {
                unset($card);
                continue;
            }

            $cards[] = $card->getPropertyArray($this);
            unset($card);
        }
       
        return $cards;
    }

    public function updateCardObjectInDb($card) 
    {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        $this->DbQuery($sql);
    }

    public function getGameDeckObject() 
    {
        return $this->cards;
    }

    public function getPlayerFactionDeckName($playerId) 
    {
        return "Faction-$playerId";
    }

    public function getPlayerDiscardDeckName($playerId) 
    {
        return "Discard-$playerId";
    }

    public function getPlayerLockerName($playerId) 
    {
        return "Locker-$playerId";
    }

    public function playerDrawCard($playerId): Card
    {
        $location = $this->getPlayerFactionDeckName($playerId);

        //If faction deck is empty move cards from player discard to faction deck
        if ($this->cards->countCardsInLocation($location, $playerId) == 0)
        {
            $this->shufflePlayerDiscardIntoPlayerFactionDeck($playerId);
        }

        $cardInfo = $this->cards->pickCard($location, $playerId);
        $card = $this->getCardObjectFromDb($cardInfo['id']);
        $card->ControllerId = $playerId;
        $card->OwnerId = $playerId;
        $card->Location = Game::LOCATION_HAND;
        $this->updateCardObjectInDb($card);

        return $card;
    }

   
    public function getCardsOnTopOfCityDeck(int $nbr): Array
    {
        $count = $this->cards->countCardsInLocation(Game::LOCATION_CITY_DECK);
        if ($count < $nbr)
        {
            $cards = $this->cards->getCardsOnTop($count, Game::LOCATION_CITY_DECK);
            $ids = array_map(function($card) { return $card['id']; }, $cards);

            $this->shuffleCityDiscardIntoCityDeck();

            //Stick cards already revealed to the top of the deck
            $ids = array_reverse($ids);
            //Insert the cards at the top of the deck
            foreach ($ids as $id)
            {
                $this->cards->insertCardOnExtremePosition($id, Game::LOCATION_CITY_DECK, true);
            }
        }

        return $this->cards->getCardsOnTop($nbr, Game::LOCATION_CITY_DECK);
    }

    public function shuffleCityDiscardIntoCityDeck()
    {
        while($this->cards->countCardsInLocation(Game::LOCATION_CITY_DISCARD) > 0) 
        {
            $cardInfo = $this->cards->getCardOnTop(Game::LOCATION_CITY_DISCARD);
            $this->cards->moveCard($cardInfo['id'], Game::LOCATION_CITY_DECK);
            $card = $this->getCardObjectFromDb($cardInfo['id']);
            $card->Location = Game::LOCATION_CITY_DECK;
            $this->updateCardObjectInDb($card);
        }
        $this->cards->shuffle(Game::LOCATION_CITY_DECK);

        $this->notifyAllPlayers("cityDiscardShuffled", clienttranslate('The City Discard Pile has been shuffled into the City Deck.'), []);
    }

    public function getCardsOnTopOfPlayerFactionDeck($playerId, int $nbr): Array
    {
        $location = $this->getPlayerFactionDeckName($playerId);

        //If faction deck is empty move cards from player discard to faction deck
        $count = $this->cards->countCardsInLocation($location);
        if ($count < $nbr)
        {
            $cards = $this->cards->getCardsOnTop($count, $location);
            $ids = array_map(function($card) { return $card['id']; }, $cards);

            $this->shufflePlayerDiscardIntoPlayerFactionDeck($playerId);

            //Stick cards already revealed to the top of the deck
            $ids = array_reverse($ids);
            //Insert the cards at the top of the deck
            foreach ($ids as $id)
            {
                $this->cards->insertCardOnExtremePosition($id, $location, true);
            }
        }

        return $this->cards->getCardsOnTop($nbr, $location);
    }

    public function shufflePlayerDiscardIntoPlayerFactionDeck($playerId)
    {
        $location = $this->getPlayerFactionDeckName($playerId);
        $discardLocation = $this->getPlayerDiscardDeckName($playerId);
        while($this->cards->countCardsInLocation($discardLocation) > 0) 
        {
            $cardInfo = $this->cards->getCardOnTop($discardLocation);
            $this->cards->moveCard($cardInfo['id'], $location);
            $card = $this->getCardObjectFromDb($cardInfo['id']);
            $card->Location = $location;
            $this->updateCardObjectInDb($card);
        }
        $this->cards->shuffle($location);

        $this->notifyAllPlayers("playerDiscardShuffled", clienttranslate('The Discard Pile of ${player_name} has been shuffled into their Faction Deck.'), [
            'player_name' => $this->getPlayerNameById($playerId),
            'playerId' => $playerId,
        ]);
    }
}
