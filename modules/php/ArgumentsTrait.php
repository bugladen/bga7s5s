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

    public function argsHighDramaBeginning_01144(): array{
        return [
            "discount" => $this->globals->get(GAME::RECRUIT_DISCOUNT)
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