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
            "discount" => $this->globals->get(GAME::DISCOUNT)
        ];
    }

    public function argsPlanningPhaseResolveSchemes_01152_3(): array
    {
        return [
            "location" => $this->globals->get(GAME::CHOSEN_LOCATION)
        ];
    }

    public function argPlayerTurn(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        return [
            "canMove" => $this->theah->playerCanMove($playerId),
            "canRecruit" => $this->theah->playerCanRecruit($playerId),
            "canEquip" => $this->handHasAttachments($playerId) || $this->theah->playerCanEquip($playerId),
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

        $performerId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->theah->getCharacterById($performerId);
        $currentLocation = $performer->Location;

        $locations = $this->theah->getAdjacentCityLocations($currentLocation);

        return [
            "selectedPerformerId" => $performerId,
            "locations" => $locations            
        ];
    }

    public function argsHighDramaRecruitActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersByPlayerId($playerId);
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  

        $charactersThatCanReruit = [];
        foreach ($characters as $character) {
            $charactersAtLocation = $this->theah->getCharactersAtLocation($character->Location);
            $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return in_array("Mercenary", $character->Traits); });
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanReruit);

        return [
            "ids" => $characterIds
        ];

    }

    public function argsHighDramaRecruitActionParley(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_CARD);

        return [
            "selectedPerformerId" => $performerId,
        ];
    }    

    public function argsHighDramaRecruitActionChooseMercenary(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_CARD);

        return [
            "discount" => $this->globals->get(GAME::DISCOUNT),
            "selectedPerformerId" => $performerId,
        ];
    }

    public function argsHighDramaEquipActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersByPlayerId($playerId);
        
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  

        $charactersThatCanEquip = [];
        foreach ($characters as $character) {
            $attachmentsAtLocation = $this->theah->getAvailalbleAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0) {
                $charactersThatCanEquip[] = $character;
            }
        }

        $charactersAtHome = $this->theah->getCharactersAtHome($playerId);
        $charactersThatCanEquip = array_merge($charactersThatCanEquip, $charactersAtHome);

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanEquip);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaEquipActionChooseAttachmentLocation(): array
    {
        $this->theah->buildCity();
        $playerId = (int)$this->getActivePlayerId();
        $performerId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->theah->getCharacterById($performerId);
        $discount = $this->globals->get(GAME::DISCOUNT);

        $attachmentsInHand = $this->getAttachmentsInHand($playerId);
        $attachmentsInPlay = [];
        if ($performer->Location != Game::LOCATION_PLAYER_HOME) 
        {
            $attachmentsInPlay = $this->theah->getAvailalbleAttachmentsAtLocation($performer->Location);
        }
        
        return [
            "selectedPerformerId" => $performerId,
            "discount" => $discount,
            "attachmentsInHand" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInHand),
            "attachmentsInPlay" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInPlay),
        ];
    }

}