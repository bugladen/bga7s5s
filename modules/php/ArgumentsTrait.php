<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait ArgumentsTrait
{
    /**
     * Game state arguments, example content.
     *
     * This method returns some additional information that is very specific to the `playerTurn` game state.
     *
     * @return array
     * @see ./states.inc.php
     */

    public function argsEmpty(): array
    {
        return [];
    }    

    public function argAvailableDecks(): array
    {
        require('includes/starterdecks.inc.php');
        
        $starter_decks = json_decode($this->starter_decks);        
        $decks = array_map(function($deck) { 
            return [ 
                "id" => $deck->id,
                "name" => $deck->name
            ]; 
        }, $starter_decks->decks);

        return ["availableDecks" => $decks];
    }

    public function argsPlanningPhaseResolveSchemes_01016_2(): array
    {
        //Return all the Red Hand Thug cards in player's deck
        if ($this->getCurrentPlayerId() == $this->getActivePlayerId()) 
        {
            $playerId = $this->getActivePlayerId();
            $location = $this->getPlayerFactionDeckName($playerId);
            $deck = $this->cards->getCardsInLocation($location);
            $thugs = [];
            foreach ($deck as $deckCard) {
                $card = $this->getCardObjectFromDb($deckCard['id']);
                if (in_array("Red Hand", $card->Traits) && in_array("Thug", $card->Traits)) 
                {
                    $thugs[] = $card->getPropertyArray();
                }
            }            

            return [
                "thugs" => $thugs
            ];
        }

        return [];
    }

    public function argsPlanningPhaseResolveSchemes_01016_3(): array
    {
        $id = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($id);
        return [
            "card" => $card->getPropertyArray()
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01125_3(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01144_2(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argsPlanningPhaseEnd_01098_2(): array
    {
        $id = $this->globals->get(GAME::CATS_EMBARGO);
        $card = $this->getCardObjectFromDb($id);
        return [
            "card" => $card->getPropertyArray()
        ];
    }

    public function argsHighDramaBeginning_01144(): array{
        return [
            "discount" => $this->globals->get(GAME::RECRUIT_DISCOUNT)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01152_3(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argsHighDramaMoveActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $characters = $this->theah->getCharactersByPlayerId($playerId);
        
        //Filter out those characters that are engaged
        $characters = array_filter($characters, function($character) { return $character->Engaged == false; });  

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $characters);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaMoveActionChooseDestination(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characterId = $this->globals->get(GAME::CHOSEN_CARD);
        $character = $this->theah->getCharacterById($characterId);
        $currentLocation = $character->Location;

        $locations = $this->theah->getAdjacentCityLocations($currentLocation);

        return [
            "selectedCharacterId" => $characterId,
            "locations" => $locations            
        ];
    }

    public function argPlayerTurn(): array
    {
        $player_id = (int)$this->getActivePlayerId();
        // $cards = $this->cards->getCardsInLocation('hand', $player_id);
        // $playableCardsIds = array_map(function($card) { return $card['id']; }, $cards);
        $playableCardsIds = [];

        return [
            "playableCardsIds" => $playableCardsIds,
        ];
    }

}