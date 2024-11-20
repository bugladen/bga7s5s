<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

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

        //Reveal the cards
        foreach ( $players as $playerId => $player ) {

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

            //Update the scheme's location in the DB
            $this->cards->moveCard($player['schemeId'], Game::LOCATION_PLAYER_HOME, $playerId);

            // Run events that the scheme has been played to a location
            $scheme = $this->theah->getPurgatoryCardById($player['schemeId']);
            $event = $this->theah->createEvent(Events::SchemeCardPlayed);
            if ($event instanceof EventSchemeCardPlayed) {
                $event->playerId = $playerId;
                $event->scheme = $scheme;
                $event->location = Game::LOCATION_PLAYER_HOME;
                $event->playerName = $player['player_name'];
            }
            $this->theah->queueEvent($event);
        }

        $this->theah->runEvents();
    }

    public function stPlanningPhaseDetermineFirstPlayer() {

        $this->theah->buildCity();
        $sql = "SELECT player_id, player_name, selected_scheme_id as schemeId FROM player";
        $players = $this->getCollectionFromDb($sql);

        $highInitiative = 0;
        $highPlayerId = 0;
        $tiedInitiative = false;
        $currentFirstPlayerExists = $this->globals->has("firstPlayer");

        //Grab the schemes by each player and determine the highest initiative
        foreach ( $players as $playerId => $player ) {
            $scheme = $this->theah->getCardById($player['schemeId']);
            if ($scheme instanceof Scheme)
            {
                if ($scheme->Initiative == $highInitiative) {
                    $tiedInitiative = true;
                }
                else if ($scheme->Initiative > $highInitiative) {
                    $highInitiative = $scheme->Initiative;
                    $highPlayerId = $playerId;
                }    
            }
        }

        // If we have a clear winner with no ties, set the first player and move on.
        if (! $tiedInitiative || count($players) == 1) {
            $this->globals->set("firstPlayer", $highPlayerId);
            $this->gamestate->changeActivePlayer($highPlayerId);

            // Notify all players of the first player.
            $this->notifyAllPlayers("firstPlayer", clienttranslate('${player_name} has the highest initiative of ${initiative} and will be set as First Player.'), [
                'player_name' => $players[$highPlayerId]['player_name'],
                'initiative' => $highInitiative,
                'playerId' => $highPlayerId
            ]);

            $this->theah->runEvents();
            return;
        }

        // If we have a tie for initiative. If first player exists, then simply move to the next player.
        if ($currentFirstPlayerExists) {
            //Get the current first player
            $firstPlayerId = $this->globals->get("firstPlayer");

            //Find out who the next player is in order
            $table = $this->getNextPlayerTable();
            $nextPlayerId = $table[$firstPlayerId];

            $this->globals->set("firstPlayer", $nextPlayerId);
            $this->gamestate->changeActivePlayer($nextPlayerId);

            // Notify all players of the first player.
            $this->notifyAllPlayers("firstPlayer", clienttranslate('With a tied initiative of ${initiative}, ${player_name} is the next player in order, and will be set as First Player.'), [
                'player_name' => $players[$nextPlayerId]['player_name'],
                'initiative' => $highInitiative,
                'playerId' => $nextPlayerId
            ]);

            $this->theah->runEvents();
            return;
        }

        // If we have a tie for initiative and no first player exists, then we determine first player by random method.
        // Extract all the player id keys from the $players array and shuffle them.
        $size = count($players);
        $rand = random_int(0, $size - 1);
        $slice = array_slice($players, $rand, 1, true);
        $firstPlayerId = key($slice);
        $this->gamestate->changeActivePlayer($firstPlayerId);
        $this->globals->set("firstPlayer", $firstPlayerId);

        // Notify all players of the first player.
        $this->notifyAllPlayers("firstPlayer", clienttranslate('With a tied initiative of ${initiative}, and no previous First Player, ${player_name} has been chosen randomly as the First Player.'), [
            'player_name' => $players[$firstPlayerId]['player_name'],
            'initiative' => $highInitiative,
            'playerId' => $firstPlayerId
        ]);
 
        $this->theah->runEvents();
    }

    public function stPlanningPhaseResolveWhenRevealedCards() {

        $this->theah->buildCity();

        $sql = "SELECT player_id, player_name, player_color, selected_scheme_id as schemeId, selected_character_id as characterId FROM player";
        $players = $this->getCollectionFromDb($sql);

        $whenRevealedEffectsCount = 0;
        foreach ( $players as $playerId => $player ) {
            $whenRevealedEffectsCard = null;

            $character = $this->theah->getCardById($player['characterId']);
            $scheme = $this->theah->getCardById($player['schemeId']);

            // Determine the number of "When Revealed" effects that will be triggered
            if ($character->hasWhenRevealedEffect()) {
                $whenRevealedEffectsCount++;
                $whenRevealedEffectsCard = $character;
            }
            if ($scheme->hasWhenRevealedEffect()) {
                $whenRevealedEffectsCount++;
                $whenRevealedEffectsCard = $scheme;
            }
            if ($whenRevealedEffectsCount == 1) {
                // Perform the necessary actions for the "When Revealed" effect
            }
            else if ($whenRevealedEffectsCount > 1) {
                // Go into a state where the First Player must choose which "When Revealed" effect to trigger
    
                // 1. Determine initiative for the First Player
                // 2. Go into a state where the First Player must choose which "When Revealed" effect to trigger
            }
        }

        $this->notifyAllPlayers("resolveWhenRevealedCards", clienttranslate("Resolving any WHEN REVEALED effects on cards."), []);

        $this->theah->runEvents();
    }

    public function stPlanningPhaseMuster() {
        // Muster the characters
        $this->notifyAllPlayers("muster", clienttranslate('All Players MUSTER their chosen Characters'), []);

        //Determine if any players are over their crew cap limit

        $this->theah->runEvents();
    }

    public function stPlanningPhaseResolveSchemes() {

        // Resolve schemes
        $this->notifyAllPlayers("resolveSchemes", clienttranslate('All Players RESOLVE their chosen Schemes'), []);

        // Resolve the schemes in order of first player
        $order = $this->getNextPlayerTable();        
        foreach ( $order as $playerId ) {
            $sql = "SELECT selected_scheme_id as schemeId FROM player WHERE player_id = $playerId";
            $schemeId = $this->getUniqueValueFromDB($sql);
            $scheme = $this->theah->getCardById($schemeId);

            // Run events that the scheme has been played to a location
            $event = $this->theah->createEvent(Events::ResolveScheme);
            if ($event instanceof EventResolveScheme) {
                $event->playerId = $playerId;
                $event->playerName = $this->getPlayerNameById($playerId);
                $event->scheme = $scheme;
            }
            $this->theah->queueEvent($event);
        }

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