<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait StatesTrait
{
    public function stBuildDecks() {
        $this->buildDecks();
        $this->gamestate->nextState("");
    }

    public function stMorningPhase() {
        // Increment the day
        $day = $this->getGameStateValue("day") + 1;
        $this->setGameStateValue("day", $day);

        //Set the phase to morning
        /** @disregard P1012 */
        $turnPhase = self::DAWN;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is morning
        $this->notifyAllPlayers("dawn", clienttranslate('It is <span style="font-weight:bold">DAWN</span>, the start of Day #${day} in the city of Theah.'), [
            "day" => $day,
        ]);

        /** @disregard P1012 */
        $city_locations = [self::LOCATION_CITY_DOCKS, self::LOCATION_CITY_FORUM, self::LOCATION_CITY_BAZAAR];
        if ($this->getPlayersNumber() > 2)
        {
            /** @disregard P1012 */
            array_unshift($city_locations, self::LOCATION_CITY_OLES_INN);
        }
        if ($this->getPlayersNumber() > 3) {
            /** @disregard P1012 */
            $city_locations[] = self::LOCATION_CITY_GOVERNORS_GARDEN;
        }

        foreach ($city_locations as $location) {
            //Add a city card to each location
            /** @disregard P1012 */
            $cityCard = $this->cards->getCardOnTop(self::LOCATION_CITY_DECK);
            $this->cards->moveCard($cityCard['id'], $location);

            $card = $this->getCardObjectFromDb($cityCard['id']);

            //Update the location on the card object
            $card->Location = $location;            
            $this->updateCardObjectInDb($card);

            $this->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('${card_name} added to ${location} from the city deck'), [
                "card_name" => $card->Name,
                "location" => $location,
                "card" => $card->getPropertyArray()
            ]);
        }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhase() {
        //Set the phase to planning
        /** @disregard P1012 */
        $turnPhase = self::PLANNING;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<span style="font-weight:bold">PLANNING PHASE</span>.'), [
        ]);
        
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stMultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    /**
     * Game state action, example content.
     *
     * The action method of state `nextPlayer` is called everytime the current game state is set to `nextPlayer`.
     */
    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Give some extra time to the active player when he completed an action
        $this->giveExtraTime($player_id);
        
        $this->activeNextPlayer();

        // Go to another gamestate
        // Here, we would detect if the game is over, and in this case use "endGame" transition instead 
        $this->gamestate->nextState("nextPlayer");
    }
}