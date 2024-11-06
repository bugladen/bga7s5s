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
        /** @disregard P1012 */
        $starter_decks = json_decode($this->starter_decks);        
        $decks = array_map(function($deck) { 
            return [ 
                "id" => $deck->id,
                "name" => $deck->name
            ]; 
        }, $starter_decks->decks);

        return ["availableDecks" => $decks];
    }

    public function argPlanningPhase(): array
    {
        return [];
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