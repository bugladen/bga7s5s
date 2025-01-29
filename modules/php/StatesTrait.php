<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

trait StatesTrait
{
    public function stMultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stRunEvents() {
        $this->theah->buildCity();
        $this->theah->runEvents();
    }

    public function stBuildDecks() {
        $this->buildDecks();
        $this->gamestate->nextState("");
    }

    public function stDawnNewDay() {
        // Increment the day
        $day = $this->getGameStateValue("day") + 1;
        $this->setGameStateValue("day", $day);

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
        $this->gamestate->nextState("");
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
        $this->gamestate->nextState("");
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
            unset($card);
        }

        $this->gamestate->nextState("");
    }

    public function stDawnEnding() {
        //Notify players that it is dawn beginning
        $this->notifyAllPlayers("dawnBeginning", clienttranslate('<span style="font-weight:bold">DAWN ENDING PHASE</span>.'), []);

        //Create the event
        $event = $this->theah->createEvent(Events::PhaseDawnEnding);
        $this->theah->queueEvent($event);
        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseBeginning()
    {
        //Set the phase to planning
        $turnPhase = Game::PLANNING;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<span style="font-weight:bold">PLANNING PHASE</span>.'), []);

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PhasePlanningBeginning);
        $this->theah->queueEvent($event);
        $this->gamestate->nextState("");
    }

    public function stPlanningPhase() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    public function stPlanningPhaseApproachCardsPlayed()
    {
        $sql = "SELECT player_id, player_name, player_color, selected_scheme_id as schemeId, selected_character_id as characterId FROM player";
        $players = $this->getCollectionFromDb($sql);

        //Reveal the cards
        foreach ( $players as $playerId => $player ) {

            //Update the character's location in the DB
            $this->cards->moveCard($player['characterId'], Game::LOCATION_PLAYER_HOME, $playerId);

            // Run events that the character has been played to a location
            $character = $this->getCardObjectFromDb($player['characterId']);
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
            $scheme = $this->getCardObjectFromDb($player['schemeId']);
            $event = $this->theah->createEvent(Events::SchemeCardRevealed);
            if ($event instanceof EventSchemeCardRevealed) {
                $event->playerId = $playerId;
                $event->scheme = $scheme;
                $event->location = Game::LOCATION_PLAYER_HOME;
                $event->playerName = $player['player_name'];
            }
            $this->theah->queueEvent($event);
        }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseDetermineFirstPlayer() 
    {
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
            $this->setNewPlayerOrder($highPlayerId);

            // Notify all players of the first player.
            $this->notifyAllPlayers("firstPlayer", clienttranslate('${player_name} has the highest initiative of ${initiative} and will be set as ${firstPlayer}.'), [
                'player_name' => $players[$highPlayerId]['player_name'],
                'initiative' => $highInitiative,
                'playerId' => $highPlayerId,
                'firstPlayer' => "<span style='font-weight:bold; color:red'>First Player</span>"
            ]);

            $event = $this->theah->createEvent(Events::FirstPlayerDetermined);
            $this->theah->queueEvent($event);
            $this->gamestate->nextState("");
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
            $this->setNewPlayerOrder($nextPlayerId);

            // Notify all players of the first player.
            $this->notifyAllPlayers("firstPlayer", clienttranslate('With a tied initiative of ${initiative}, ${player_name} is the next player in order, and will be set as ${firstPlayer}.'), [
                'player_name' => $players[$nextPlayerId]['player_name'],
                'initiative' => $highInitiative,
                'playerId' => $nextPlayerId,
                'firstPlayer' => "<span style='font-weight:bold; color:red'>First Player</span>"
            ]);

            $event = $this->theah->createEvent(Events::FirstPlayerDetermined);
            $this->theah->queueEvent($event);
            $this->gamestate->nextState("");
            return;
        }

        // If we have a tie for initiative and no first player exists, then we determine first player by random method.
        // Extract all the player id keys from the $players array and shuffle them.
        $size = count($players);
        $rand = random_int(0, $size - 1);
        $slice = array_slice($players, $rand, 1, true);
        $firstPlayerId = key($slice);
        $this->globals->set("firstPlayer", $firstPlayerId);
        $this->setNewPlayerOrder($firstPlayerId);

        // Notify all players of the first player.
        $this->notifyAllPlayers("firstPlayer", clienttranslate('With a tied initiative of ${initiative}, and no previous First Player, ${player_name} has been chosen randomly as the First Player.'), [
            'player_name' => $players[$firstPlayerId]['player_name'],
            'initiative' => $highInitiative,
            'playerId' => $firstPlayerId
        ]);
 
        $event = $this->theah->createEvent(Events::FirstPlayerDetermined);
        $this->theah->queueEvent($event);
        $this->gamestate->nextState("");
    }   

    public function stPlanningPhaseResolveWhenRevealedCards() 
    {
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

        $this->notifyAllPlayers("message", clienttranslate("Resolving any WHEN REVEALED effects on cards."), []);

        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseMuster() {
        // Muster the characters
        $this->notifyAllPlayers("message", clienttranslate('All Players MUSTER their chosen Characters in player order starting with the FIRST PLAYER.'), []);

        $sql = "SELECT player_id, player_name, leader_card_id as leaderId FROM player ORDER BY turn_order";
        $players = $this->getCollectionFromDb($sql);
        foreach ( $players as $playerId => $player ) {
            $leader = $this->theah->getCardById($player['leaderId']);
            if ($leader instanceof Leader)
                $crewCap = $leader->CrewCap;

            $characterCount = $this->theah->getCharacterCountByPlayerId($playerId);
            if ($characterCount > $crewCap) {
                //TODO: Go into a state to allow the player to remove characters
                $this->notifyAllPlayers("message", clienttranslate('${player_name} is over their Crew Cap limit of ${crewcap}'), [
                    'player_name' => $player['player_name'],
                    'crewcap' => $crewCap
                ]);
            } else {
                $this->notifyAllPlayers("message", clienttranslate('<span style=${player_name} is under their Crew Cap limit of ${crewcap}'), [
                    'player_name' => $player['player_name'],
                    'crewcap' => $crewCap
                ]);
            }
        }

        $event = $this->theah->createEvent(Events::PhaseMuster);
        $this->theah->queueEvent($event);
        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseResolveSchemes() {

        // Resolve schemes
        $this->notifyAllPlayers("message", clienttranslate('All Players RESOLVE their chosen Schemes in player order starting with the FIRST PLAYER.'), []);

        // Resolve the schemes in player order
        $sql = "SELECT player_id, selected_scheme_id as schemeId FROM player ORDER by turn_order";
        $list = $this->getCollectionFromDB($sql);
        foreach ( $list as $playerId => $player ) {
            $schemeId = $player['schemeId'];
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

        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseDraw() {
        // Draw cards
        $this->notifyAllPlayers("message", clienttranslate('All Players DRAW cards.'), []);

        $players = $this->loadPlayersBasicInfos();
        foreach ( $players as $playerId => $player ) {
            //Get the player's leader
            $leader = $this->theah->getLeaderByPlayerId($playerId);
            //Get the modified panache value for the leader
            if ($leader instanceof Leader) {
                $panache = $leader->ModifiedPanache;
            }

            $cards = [];
            for ($i = 0; $i < $panache; $i++) {
                $card = $this->playerDrawCard($playerId);
                $cards[] = $card->getPropertyArray();
                unset($card);
            }

            $cardList = implode(", ", array_map(function($card) { return $card['name']; }, $cards));
            $this->notifyPlayer($playerId, "factionResolveCardDraw", 
                clienttranslate('Your panache value is: ${panache}.  As your draw you received: ${card_list}'), [
                    "panache" => $panache,
                    "card_list" => $cardList,
                    "cards" => $cards
                ]);
    }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseEnd() {
        //Notify players that it is planning phase end
        $this->notifyAllPlayers("message", clienttranslate('<span style="font-weight:bold">PLANNING PHASE END</span>.'), []);

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PhasePlanningEnd);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function stHighDramaBeginning() {
        //Set the phase to high drama
        $turnPhase = Game::HIGH_DRAMA;
        $this->setGameStateValue("turnPhase", $turnPhase);

        //Notify players that it is high drama phase
        $this->notifyAllPlayers("message", clienttranslate('<span style="font-weight:bold">HIGH DRAMA PHASE</span>.'), []);

        $event = $this->theah->createEvent(Events::PhaseHighDrama);
        $this->theah->queueEvent($event);
        
        $this->gamestate->nextState("");
    }

    public function stHighDramaPhase() {
        $this->gamestate->changeActivePlayer($this->globals->get("firstPlayer"));
        $this->gamestate->nextState("");
    }

    public function stHighDramaRecruitActionParleyable()
    {
        $id = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($id);
        if ($card instanceof Character)
        {
            if ($card->Engaged)
            {
                //Discount might have special abilities above parleying
                $discount = $card->getParleyDiscount(false);
                $this->globals->set(Game::DISCOUNT, $discount);
                $this->gamestate->nextState("notParleyable");
            }
            else
                $this->gamestate->nextState("parleyable");
        }
    }

    public function stTechniqueAvailable()
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->getCardObjectFromDb($performerId);

        if ($performer instanceof IHasTechniques)
        {
            if ($performer->anyTechniquesAvailable())
            {
                $this->gamestate->nextState("hasTechique");
                return;
            }
        }

        foreach($performer->Attachments as $attachmentId)
        {
            $attachment = $this->getCardObjectFromDb($attachmentId);
            if ($attachment instanceof IHasTechniques)
            {
                if ($attachment->anyTechniquesAvailable())
                {
                    $this->gamestate->nextState("hasTechique");
                    return;
                }
            }
        }

        //Set the turn to the target player
        $targetId = $this->globals->get(GAME::CHOSEN_TARGET);
        $target = $this->getCardObjectFromDb($targetId);
        $this->gamestate->changeActivePlayer($target->ControllerId);

        $this->gamestate->nextState("noTechnique");
    }

    public function stSetupChallenge()
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        if ($this->globals->has(GAME::CHOSEN_TECHNIQUE))
        {
            $techniqueId = $this->globals->get(GAME::CHOSEN_TECHNIQUE);
            $technique = $this->theah->getTechniqueById($techniqueId);
        }
        else
        {
            $technique = null;
        }

        $engageEvent = $this->theah->createEvent(Events::CardEngaged);
        if ($engageEvent instanceof EventCardEngaged)
        {
            $engageEvent->card = $performer;
            $engageEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($engageEvent);
        $this->theah->queueEvent($engageEvent);

        $challengeEvent = $this->theah->createEvent(Events::ChallengeIssued);
        if ($challengeEvent instanceof EventChallengeIssued)
        {
            $challengeEvent->playerId = $playerId;
            $challengeEvent->performer = $performer;
            $challengeEvent->target = $target;
            $challengeEvent->activatedTechnique = $technique;
        }

        try 
        {
            $this->theah->eventCheck($challengeEvent);
        }
        catch (\Exception $e) {
            $this->game->notifyAllPlayers("message", clienttranslate($e->getMessage()), []);
            $this->gamestate->nextState("challengeFailed");
            return;
        }

        $this->theah->queueEvent($challengeEvent);
        $this->gamestate->changeActivePlayer($target->ControllerId);
        $this->gamestate->nextState("challengeSetUp");
    }

    public function stHighDramaChallengeActionResolveTechnique(): void
    {
        $this->theah->buildCity();

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $targetId = $this->globals->get(GAME::CHOSEN_TARGET);
        $target = $this->theah->getCharacterById($targetId);

        $techniqueId = $this->globals->get(GAME::CHOSEN_TECHNIQUE);
        $technique = $this->theah->getTechniqueById($techniqueId);

        $ownerId = $technique->OwnerId;
        $techniqueOwner = $this->theah->getCardById($ownerId);

        $event = $this->theah->createEvent(Events::ResolveTechnique);
        if ($event instanceof EventResolveTechnique)
        {
            $event->playerId = $performer->ControllerId;
            $event->performer = $performer;
            $event->target = $target;
            $event->technique = $technique;
            $event->techniqueOwner = $techniqueOwner;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stHighDramaChallengeActionGenerateThreat()
    {
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->getCardObjectFromDb($performerId);

        $event = $this->theah->createEvent(Events::GenerateThreat);
        if ($event instanceof EventGenerateThreat)
        {
            $event->performer = $performer;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stHighDramaChallengeActionResolution()
    {
        if ($this->globals->get(GAME::CHALLENGE_ACCEPTED))
        {
            $this->gamestate->nextState("accepted");
        }
        else
        {
            //Challege was rejected, wound the target by the threat value.  Limit amount done by the performer's Combat value.
            $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $this->getCardObjectFromDb($performerId);
            $targetId = $this->globals->get(GAME::CHOSEN_TARGET);
            $target = $this->getCardObjectFromDb($targetId);

            $threat = $this->globals->get(GAME::CHALLENGE_THREAT);
            $combat = $performer->ModifiedCombat;
            $wounds = $threat;
            $reason = "Challenge Rejected. Generated Threat was {$threat}.";
            if ($threat > $combat)
            {
                $wounds = $combat;
                $reduction = $threat - $combat;
                $reason .= "  Threat reduced by {$reduction} due to Restricted Hostilities (Combat value of {$combat}). ";
            }

            $event = $this->theah->createEvent(Events::CharacterWounded);
            if ($event instanceof EventCharacterWounded)
            {
                $event->character = $target;
                $event->wounds = $wounds;
                $event->reason = $reason;
            }
            $this->theah->eventCheck($event);
            $this->theah->queueEvent($event);
            
            $this->gamestate->nextState("rejected");
        }        
    }

    public function stNextPlayer(): void {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        $this->giveExtraTime($player_id);
        $this->activeNextPlayer();

        $this->gamestate->nextState("nextPlayer");
    }
}