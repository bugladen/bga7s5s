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
            "canChallenge" => $this->theah->playerCanChallenge($playerId),
            "canClaim" => $this->theah->playerCanClaim($playerId),
            "canEquip" => $this->theah->playerCanEquip($playerId),
            "canMove" => $this->theah->playerCanMove($playerId),
            "canRecruit" => $this->theah->playerCanRecruit($playerId),
        ];
    }

    public function argsHighDramaMoveActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        
        //Filter out those characters that are engaged
        $characters = array_values(array_filter($characters, function($character) { return $character->Engaged == false; }));

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
            "performerId" => $performerId,
            "locations" => $locations            
        ];
    }

    public function argsHighDramaRecruitActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
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
            "performerId" => $performerId,
        ];
    }    

    public function argsHighDramaRecruitActionChooseMercenary(): array
    {
        $performerId = $this->globals->get(GAME::CHOSEN_CARD);

        return [
            "discount" => $this->globals->get(GAME::DISCOUNT),
            "performerId" => $performerId,
        ];
    }

    public function argsHighDramaEquipActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        $charactersInCity = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  

        $handHasAttachments = $this->handHasAttachments($playerId);
        $charactersThatCanEquip = [];
        foreach ($charactersInCity as $character) {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0 || $handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }

        $charactersAtHome = $this->theah->getCharactersAtHome($playerId);
        foreach($charactersAtHome as $character) {
            if ($handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }

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
            $attachmentsInPlay = $this->theah->getAvailableAttachmentsAtLocation($performer->Location);
        }
        
        return [
            "performerId" => $performerId,
            "discount" => $discount,
            "attachmentsInHand" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInHand),
            "attachmentsInPlay" => array_map(function($attachment) { return $attachment->Id; }, $attachmentsInPlay),
        ];
    }

    public function argsHighDramaClaimActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        
        //Filter out those characters that are not in the city
        $characters = array_values(array_filter($characters, fn($character) => $this->theah->cardInCity($character) ));

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $characters);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaChallengeActionChoosePerformer(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        
        //Filter out those characters that are not in the city
        $characters = array_values(array_filter($characters, fn($character) => $this->theah->cardInCity($character) ));
        $characters = array_values(array_filter($characters, fn($character) => !$character->Engaged ));

        //Select the Ids of the characters
        $characterIds = array_map(fn($character) => $character->Id, $characters);

        return [
            "ids" => $characterIds
        ];
    }

    public function argsHighDramaChallengeActionChooseTarget(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        //
        $charactersAtLocation = array_values(array_filter($charactersAtLocation, fn($character) => $character->ControllerId && $character->ControllerId != $playerId));
        $ids = array_map(fn($character) => $character->Id, $charactersAtLocation);

        return [
            "performerId" => $performerId,
            "ids" => $ids
        ];
    }

    public function argsHighDramaChallengeActionActivateTechnique(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $targetId = $this->globals->get(Game::CHOSEN_TARGET);
        $target = $this->theah->getCharacterById($targetId);

        return [
            "performerId" => $performerId,
            "targetId" => $targetId,
            "techniques" => $this->theah->getAvailableCharacterTechniques($performer)
        ];
    }

    public function argsHighDramaChallengeActionAcceptChallenge(): array
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        $performerId = $this->globals->get(Game::CHOSEN_PERFORMER);
        $targetId = $this->globals->get(Game::CHOSEN_TARGET);
        $target = $this->theah->getCharacterById($targetId);

        //Get a list of characters that could intervene
        $charactersAtLocation = $this->theah->getCharactersAtLocation($target->Location);
        //Characters must be controlled by the player and not be the target
        $charactersAtLocation = array_filter($charactersAtLocation, 
            fn($character) => $character->ControllerId && $character->ControllerId == $playerId && $character->Id != $targetId);
        //Characters must not be engaged
        $charactersAtLocation = array_values(array_filter($charactersAtLocation, fn($character) => $character->Engaged == false));

        $ids = array_map(fn($character) => $character->Id, $charactersAtLocation);

        return [
            "performerId" => $performerId,
            "targetId" => $targetId,
            "ids" => $ids
        ];

    }

    public function argsChooseDuelAction(): array
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $round = $this->getObjectListFromDB($sql)[0];

        $actorId = $round['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);

        $maneuevers = $this->theah->getAvailableCharacterManeuvers($actor);
        $techniques = $this->theah->getAvailableCharacterTechniques($actor);

        return [
            "maneuverAvailable" => count($maneuevers) > 0 && $round['maneuver_id'] == null,
            "techniqueAvailable" => count($techniques) > 0 && $round['technique_id'] == null
        ];
    }

    public function argsChooseDuelTechnique(): array
    {
        $this->theah->buildCity();
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $round = $this->getObjectListFromDB($sql)[0];

        $actorId = $round['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);

        $techniques = $this->theah->getAvailableCharacterTechniques($actor);
        return [
            "techniques" => $techniques
        ];

    }

}