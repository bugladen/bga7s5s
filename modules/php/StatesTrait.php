<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;

trait StatesTrait
{
    public function stMultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stBuildDecks() {
        $this->buildDecks();
        $this->gamestate->nextState("");
    }

    public function stDawnNewDay() {
        // Increment the day
        $day = $this->getGameStateValue("day") + 1;
        $this->setGameStateValue("day", $day);

        $this->theah->buildCity();

        //Notify players that it is morning
        $this->notifyAllPlayers("dawn", clienttranslate('It is <span style="font-weight:bold">DAWN</span>, the start of Day #${day} in the city of Theah.'), [
            "day" => $day,
        ]);

        //New day Theah event
        $event = $this->theah->createEvent(Events::NEW_DAY);
        if ($event instanceof EventNewDay) {
            $newDay = $event;
            $newDay->dayNumber = $day;
        }
        $this->theah->queueEvent($event);
        $this->theah->runQueuedEvents();

        //Set the phase to morning
        $turnPhase = Game::DAWN;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Create the morning event
        $event = $this->theah->createEvent(Events::MORNING);
        $this->theah->queueEvent($event);
        $this->theah->runQueuedEvents();

        //Create the core city locations
        $city_locations = [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR];

        // Add Ole's Inn if there are more than 2 players
        if ($this->getPlayersNumber() > 2)
        {
            array_unshift($city_locations, Game::LOCATION_CITY_OLES_INN);
        }

        // Add the Governor's Garden if there are more than 3 players
        if ($this->getPlayersNumber() > 3) {
            $city_locations[] = Game::LOCATION_CITY_GOVERNORS_GARDEN;
        }

        //Add a city card to each location
        foreach ($city_locations as $location) {

            //First see if there is a debug value to include a specific city card
            if ($this->globals->has(Game::DEBUG_INCLUDE_CITY_CARD)) {
                //Get the class name
                $debugCityCard = $this->globals->get(Game::DEBUG_INCLUDE_CITY_CARD);
                //Grab an array by type
                $cityCard = $this->cards->getCardsOfType($debugCityCard);
                //Get the first card in the array
                $cityCard = array_shift($cityCard);
                //Remove the debug value
                $this->globals->delete(Game::DEBUG_INCLUDE_CITY_CARD);
            } else {
                $cityCard = $this->cards->getCardOnTop(Game::LOCATION_CITY_DECK);
            }

            $this->cards->moveCard($cityCard['id'], $location);

            $card = $this->getCardObjectFromDb($cityCard['id']);

            //Create the event
            $event = $this->theah->createEvent(Events::CITY_CARD_ADDED_TO_LOCATION);
            if ($event instanceof EventCityCardAddedToLocation) {
                $cityEvent = $event;
                $cityEvent->card = $card;
                $cityEvent->location = $location;
            }
            $this->theah->queueEvent($event);
            $this->theah->runQueuedEvents();
        }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhase() {
        //Set the phase to planning
        $turnPhase = Game::PLANNING;
        $this->setGameStateValue("turnPhase", $turnPhase);

        $this->theah->buildCity();

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PLANNING_PHASE);
        $this->theah->queueEvent($event);
        $this->theah->runQueuedEvents();

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<span style="font-weight:bold">PLANNING PHASE</span>.'), [
        ]);
        
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stHighDramaPhase() {
        //Set the phase to high drama
        $turnPhase = Game::HIGH_DRAMA;
        $this->setGameStateValue("turnPhase", $turnPhase);

        $this->theah->buildCity();

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PLANNING_PHASE);
        $this->theah->queueEvent($event);
        $this->theah->runQueuedEvents();

        $sql = "SELECT player_id, player_name, player_color, selected_scheme_id as schemeId, selected_character_id as characterId FROM player";
        $players = $this->getCollectionFromDb($sql);
        foreach ( $players as $playerId => $player ) {

            //Update the scheme's location in the DB
            $this->cards->moveCard($player['schemeId'], Game::LOCATION_PLAYER_HOME, $playerId);

            $event = $this->theah->createEvent(Events::SCHEME_CARD_PLAYED);
            $scheme = $this->theah->getPurgatoryCardById($player['schemeId']);
            if ($event instanceof EventSchemeCardPlayed) {
                $approach = $event;
                $approach->playerId = $playerId;
                $approach->scheme = $scheme;
                $approach->location = Game::LOCATION_PLAYER_HOME;
                $approach->playerName = $player['player_name'];
            }
            $this->theah->queueEvent($event);
            $this->theah->runQueuedEvents();

            //Update the character's location in the DB
            $this->cards->moveCard($player['characterId'], Game::LOCATION_PLAYER_HOME, $playerId);

            // Run events that the character has been played to a location
            $character = $this->theah->getPurgatoryCardById($player['characterId']);
            $event = $this->theah->createEvent(Events::APPROACH_CHARACTER_PLAYED);
            if ($event instanceof EventApproachCharacterPlayed) {
                $approach = $event;
                $approach->playerId = $playerId;
                $approach->character = $character;
                $approach->location = Game::LOCATION_PLAYER_HOME;
                $approach->playerName = $player['player_name'];
            }
            $this->theah->queueEvent($event);
            $this->theah->runQueuedEvents();
        }

        //TODO: Compare the initiative of the schemes and determine the first player        

        //Notify players that it is high drama phase
        $this->notifyAllPlayers("highDramaPhase", clienttranslate('<span style="font-weight:bold">HIGH DRAMA PHASE</span>.'), [
        ]);

        $this->gamestate->nextState("");
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