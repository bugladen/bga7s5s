<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

trait DeckTrait
{
    public function buildDecks() {

        // *** Create the city deck ***

        // Load the city deck JSON
        $city_decks = json_decode($this->city_decks);

        // TODO: City deck loaded should be based on option
        // Pull the city deck with id of '7s5s'
        $city = current(array_filter($city_decks->decks, 
            function($deck) {
                return $deck->id === '7s5s';
            }
        ));

        foreach ($city->cards as $card) {
            $location = Game::LOCATION_CITY_DECK;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$card}', 0, '{$location}', 0)";
            $this->DbQuery($sql);

            //Store the card Id in the object, and serialize the card object to the db
            $id = $this->DbGetLastId();
            $card = $this->instantiateCard($card);
            $card->Id = $id;
            $this->updateCardObjectInDb($card);
        }
        $this->cards->shuffle(Game::LOCATION_CITY_DECK);

        // Load the decks selected by the players
        $starter_decks = json_decode($this->starter_decks);
        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $playerId => $player ) {

            // Get the source and deck_id of the deck from the DB for the  player
            $result = $this->getObjectFromDB("SELECT deck_source, deck_id FROM player WHERE player_id = '$playerId'");
            $source = $result['deck_source'];
            $deck_id = $result['deck_id'];

            if ($source === 'starter') {
                $deck = current(array_filter($starter_decks->decks, 
                    function($deck) use ($deck_id) {
                        return $deck->id === $deck_id;
                    }
                ));
            }
            
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
                $card->Id = $id;
                $card->Location = $location;
                $this->updateCardObjectInDb($card);

                //Set the id of the leader card in the player record
                $sql = "UPDATE player SET leader_card_id = $id WHERE player_id = $playerId";
                $this->DbQuery($sql);

                //Notify players about the leaders
                $this->notifyAllPlayers("playLeader", clienttranslate('${player_name} plays ${player_faction} Faction and ${leader_name} as their leader.'), [
                    "player_name" => $player['player_name'],
                    "player_faction" => "<span style='font-weight:bold'>{$card->Faction}</span>",
                    "leader_name" => "<span style='font-weight:bold'>{$card->Name}</span>",
                    "player_id" => $playerId,
                    "player_color" => $player['player_color'],
                    "leader" => $card->getPropertyArray(),
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
                $card->Id = $id;
                $card->OwnerId = $playerId;
                $card->ControllerId = $playerId;
                $card->Location = $location;
                $this->updateCardObjectInDb($card);

                $cards[] = $card->getPropertyArray();
            }

            $cardList = implode(", ", array_map(function($card) { return $card['name']; }, $cards));
            $this->notifyPlayer($playerId, "approachCardsReceived", 
                clienttranslate('You received your Approach Deck containing: ${card_list}'), [
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
                    $card->Id = $id;
                    $card->OwnerId = $playerId;
                    $card->ControllerId = $playerId;
                    $card->Location = $location;
                    $this->updateCardObjectInDb($card);

                    $cards[] = $card->getPropertyArray();

                }
            }
            $this->cards->shuffle($location, $playerId);
        }
    }

    public function getCardsInLocation($location)
    {
        $cards = [];
        $locationCards = $this->cards->getCardsInLocation($location);
        foreach ($locationCards as $cardId) {
            $card = $this->getCardObjectFromDb($cardId['id']);
            $cards[] = $card->getPropertyArray();
            unset($card);
        }
        
        return $cards;
    }

    // Only methods that are used in the DeckTrait should be using this method.  
    // Otherwise, create an event and let Theah handle it.
    private function updateCardObjectInDb($card) {
        $serialized = addslashes(serialize($card));
        $sql = "UPDATE card set card_serialized = '{$serialized}' WHERE card_id = $card->Id";
        $this->DbQuery($sql);
    }
}