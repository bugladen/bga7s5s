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

        //Notify players that it is Dawn, New Day
        $this->notifyAllPlayers("newDay", clienttranslate('It is the start of <span style="font-weight:bold">DAY #${day}</span> in the city of Theah.'), [
            "day" => $day,
        ]);

        //New day Theah event
        $event = $this->theah->createEvent(Events::NewDay);
        if ($event instanceof EventNewDay) {
            $event->dayNumber = $day;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents();
    }

    public function stDawnBeginning() {
        //Set the phase to morning
        $turnPhase = Game::DAWN;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is dawn beginning
        $this->notifyAllPlayers("dawnBeginning", clienttranslate('<span style="font-weight:bold">DAWN BEGINNING PHASE</span>.'), []);

        //Create the event
        $event = $this->theah->createEvent(Events::PhaseDawnBeginning);
        $this->theah->queueEvent($event);
        $this->theah->runEvents();
    }

    public function stDawnCityCards() {
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
            $event = $this->theah->createEvent(Events::CityCardAddedToLocation);
            if ($event instanceof EventCityCardAddedToLocation) {
                $event->card = $card;
                $event->location = $location;
            }
            $this->theah->queueEvent($event);
        }

        $this->theah->runEvents();
    }

    public function stDawnEnding() {
        //Notify players that it is dawn beginning
        $this->notifyAllPlayers("dawnBeginning", clienttranslate('<span style="font-weight:bold">DAWN ENDING PHASE</span>.'), []);

        //Create the event
        $event = $this->theah->createEvent(Events::PhaseDawnEnding);
        $this->theah->queueEvent($event);
        $this->theah->runEvents();
    }

    public function stPlanningPhaseBeginning()
    {
        //Set the phase to planning
        $turnPhase = Game::PLANNING;
        $this->setGameStateValue("turnPhase", $turnPhase);

        $this->theah->buildCity();

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<span style="font-weight:bold">PLANNING PHASE</span>.'), []);

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PhasePlanningBeginning);
        $this->theah->queueEvent($event);
        $this->theah->runEvents();
    }

    public function stPlanningPhase() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stPlanningPhaseApproachCardsPlayed()
    {
        $this->theah->buildCity();

        $sql = "SELECT player_id, player_name, player_color, selected_scheme_id as schemeId, selected_character_id as characterId FROM player";
        $players = $this->getCollectionFromDb($sql);
        foreach ( $players as $playerId => $player ) {

            //Update the scheme's location in the DB
            $this->cards->moveCard($player['schemeId'], Game::LOCATION_PLAYER_HOME, $playerId);

            $event = $this->theah->createEvent(Events::SchemeCardPlayed);
            $scheme = $this->theah->getPurgatoryCardById($player['schemeId']);
            if ($event instanceof EventSchemeCardPlayed) {
                $event->playerId = $playerId;
                $event->scheme = $scheme;
                $event->location = Game::LOCATION_PLAYER_HOME;
                $event->playerName = $player['player_name'];
            }
            $this->theah->queueEvent($event);

            //Update the character's location in the DB
            $this->cards->moveCard($player['characterId'], Game::LOCATION_PLAYER_HOME, $playerId);

            // Run events that the character has been played to a location
            $character = $this->theah->getPurgatoryCardById($player['characterId']);
            $event = $this->theah->createEvent(Events::ApproachCharacterPlayed);
            if ($event instanceof EventApproachCharacterPlayed) {
                $event->playerId = $playerId;
                $event->character = $character;
                $event->location = Game::LOCATION_PLAYER_HOME;
            }
            $this->theah->queueEvent($event);
        }
        //TODO: Compare the initiative of the schemes and determine the first player        
        $this->theah->runEvents();
    }

    public function stHighDramaBeginning() {
        //Set the phase to high drama
        $turnPhase = Game::HIGH_DRAMA;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PhaseHighDrama);
        $this->theah->queueEvent($event);
        $this->theah->runEvents();
    }

    public function stHighDramaPhase() {
        //Notify players that it is high drama phase
        $this->notifyAllPlayers("highDramaPhase", clienttranslate('<span style="font-weight:bold">HIGH DRAMA PHASE</span>.'), []);
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