<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeSentToLocker;

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
        $this->notifyAllPlayers("newDay", clienttranslate('It is the start of <strong>DAY #${day}</strong> in the city of Theah.'), [
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
        $this->setGameStateValue(Game::TURN_PHASE, Game::DAWN);

        //Notify players that it is dawn beginning
        $this->notifyAllPlayers("dawnBeginning", clienttranslate('<strong>DAWN BEGINNING PHASE</strong>'), []);

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

            //Create the event
            $event = EventFactory::createCityCardAddedToLocationEvent($cityCard['id'], $location);
            $this->theah->queueEvent($event);
        }

        $this->gamestate->nextState("");
    }

    public function stDawnEnding() {
        //Notify players that it is dawn beginning
        $this->notifyAllPlayers("dawnBeginning", clienttranslate('<strong>DAWN ENDING PHASE</strong>'), []);

        //Create the event
        $event = $this->theah->createEvent(Events::PhaseDawnEnding);
        $this->theah->queueEvent($event);
        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseBeginning()
    {
        //Set the phase to planning
        $this->setGameStateValue(Game::TURN_PHASE, Game::PLANNING);

        //Notify players that it is planning phase
        $this->notifyAllPlayers("planningPhase", clienttranslate('<strong>PLANNING PHASE</strong>'), []);

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
        $currentFirstPlayerExists = $this->globals->has(Game::FIRST_PLAYER);

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
            $this->globals->set(Game::FIRST_PLAYER, $highPlayerId);
            $this->globals->set(Game::CURRENT_PLAYER, $highPlayerId);
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
            $firstPlayerId = $this->globals->get(Game::FIRST_PLAYER);

            //Find out who the next player is in order
            $table = $this->getNextPlayerTable();
            $nextPlayerId = $table[$firstPlayerId];

            $this->globals->set(Game::FIRST_PLAYER, $nextPlayerId);
            $this->globals->set(Game::CURRENT_PLAYER, $nextPlayerId);
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
        $this->globals->set(Game::FIRST_PLAYER, $firstPlayerId);
        $this->globals->set(Game::CURRENT_PLAYER, $firstPlayerId);
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
        foreach ( $players as $playerId => $player ) 
        {
            //Get the player's leader
            $leader = $this->theah->getLeaderByPlayerId($playerId);
            //Get the modified panache value for the leader
            if ($leader instanceof Leader) {
                $panache = $leader->ModifiedPanache;
            }

            $cards = [];
            for ($i = 0; $i < $panache; $i++) {
                $card = $this->playerDrawCard($playerId);
                $cards[] = $card->getPropertyArray($this);
                unset($card);
            }

            $cardList = implode(", ", array_map(function($card) { return self::_($card['name']); }, $cards));
            $this->notifyPlayer($playerId, "factionResolveCardDraw", 
                clienttranslate('Your panache value is: ${panache}.  As your draw you received: ${card_list}'), [
                    "panache" => $panache,
                    "card_list" => $cardList,
                    "cards" => $cards
            ]);

            $this->notifyAllPlayers("factionResolveCardDrawPublic", clienttranslate('${player_name} drew ${count} card(s).'), [
                'player_name' => $player['player_name'],
                'playerId' => $playerId,
                'count' => $panache
            ]);

        }

        $this->gamestate->nextState("");
    }

    public function stPlanningPhaseEnd() 
    {
        //Notify players that it is planning phase end
        $this->notifyAllPlayers("message", clienttranslate('<strong>PLANNING PHASE END</strong>.'), []);

        //Create the Planning phase event
        $event = $this->theah->createEvent(Events::PhasePlanningEnd);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function stHighDramaBeginning() 
    {
        //Set the phase to high drama
        $this->setGameStateValue(Game::TURN_PHASE, Game::HIGH_DRAMA);

        $this->globals->set(Game::PASS_COUNT, 0);

        //Notify players that it is high drama phase
        $this->notifyAllPlayers("message", clienttranslate('<span style="font-weight:bold">HIGH DRAMA PHASE</span>.'), []);

        $event = $this->theah->createEvent(Events::PhaseHighDrama);
        $this->theah->queueEvent($event);
        
        $this->gamestate->nextState("");
    }


    public function stHighDramaPhase() {
        $this->gamestate->changeActivePlayer($this->globals->get(Game::FIRST_PLAYER));
        $this->globals->set(Game::CLAIM_TYPE, Game::NORMAL_CLAIM_TYPE);
        $this->globals->set(Game::EQUIP_TYPE, Game::NORMAL_EQUIP_TYPE);
        $this->gamestate->nextState("");
    }


    public function stHighDramaClaim() 
    {
        $claimingPlayerId = $this->globals->get(GAME::CLAIMING_PLAYER);
        $this->gamestate->changeActivePlayer($claimingPlayerId);        
        $this->theah->buildCity();

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->getCardObjectFromDb($performerId);
        
        list($canClaim, $totals) = $this->canPlayerClaim($claimingPlayerId, $performer);
        if ( ! $canClaim) 
        {
            $this->notifyPlayer($claimingPlayerId, "message", clienttranslate('You cannot claim the location.  You do not have the most influence. Totals: ${totals}'), [
                "totals" => $totals
            ]);
            $this->gamestate->nextState("failure");
            return;
        }

        $pressureTypes = $this->theah->getPressureTypesForClaim($performer);
        $this->setControllerForLocation($performer->Location, $claimingPlayerId);

        $engageEvent = EventFactory::createCardEngagedEvent($claimingPlayerId, $performer->Id);
        $this->theah->eventCheck($engageEvent);

        $claimEvent = $this->theah->createEvent(Events::LocationClaimed);
        if ($claimEvent instanceof EventLocationClaimed)
        {
            $claimEvent->performer = $performer;
            $claimEvent->location = $performer->Location;
            $claimEvent->playerId = $claimingPlayerId;
            $claimEvent->pressureTypes = implode(", ", $pressureTypes);
            $claimEvent->totalsExplanation = $totals;
        }        
        $this->theah->eventCheck($claimEvent);

        $this->theah->queueEvent($engageEvent);
        $this->theah->queueEvent($claimEvent);    

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("success");
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

        $techniques = $this->theah->getAvailableCharacterTechniques($performer);

        if (count($techniques) > 0)
        {
            $this->gamestate->nextState("hasTechique");
            return;
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

        //Set the location of the challenge
        $this->globals->set(GAME::CHOSEN_LOCATION, $performer->Location);

        $techniqueId = "";
        if ($this->globals->has(GAME::CHOSEN_TECHNIQUE))
            $techniqueId = $this->globals->get(GAME::CHOSEN_TECHNIQUE);

        $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performer->Id);
        $this->theah->queueEvent($engageEvent);

        $challengeEvent = $this->theah->createEvent(Events::ChallengeIssued);
        if ($challengeEvent instanceof EventChallengeIssued)
        {
            $challengeEvent->playerId = $playerId;
            $challengeEvent->challengerId = $performer->Id;
            $challengeEvent->defenderId = $target->Id;
            $challengeEvent->activatedTechniqueId = $techniqueId;
        }

        $this->theah->eventCheck($challengeEvent);
        $this->theah->queueEvent($challengeEvent);
        $changeEvent = EventFactory::createChangeActivePlayerEvent($target->ControllerId);
        $this->theah->queueEvent($changeEvent);
        $this->gamestate->nextState();
    }

    public function stHighDramaChallengeActionResolveTechnique(): void
    {
        $techniqueId = $this->globals->get(GAME::CHOSEN_TECHNIQUE);
        if ($techniqueId != null)
        {
            $this->theah->buildCity();

            $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $this->theah->getCharacterById($performerId);
    
            $targetId = $this->globals->get(GAME::CHOSEN_TARGET);
            $target = $this->theah->getCharacterById($targetId);
    
            $event = $this->theah->createEvent(Events::ResolveTechnique);
            if ($event instanceof EventResolveTechnique)
            {
                $event->playerId = $performer->ControllerId;
                $event->adversaryId = $target->Id;
                $event->techniqueId = $techniqueId;
                $event->inDuel = false;
            }
            $this->theah->queueEvent($event);
        }

        $this->gamestate->nextState();
    }

    public function stHighDramaChallengeActionGenerateThreat()
    {
        $challengerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $challenger = $this->getCardObjectFromDb($challengerId);

        $defenderId = $this->globals->get(GAME::CHOSEN_TARGET);
        $defender = $this->getCardObjectFromDb($defenderId);

        $event = $this->theah->createEvent(Events::GenerateChallengeThreat);
        if ($event instanceof EventGenerateChallengeThreat)
        {
            $event->actorId = $challenger->Id;
            $event->adversaryId = $defender->Id;
            $event->techniqueId = $this->globals->get(GAME::CHOSEN_TECHNIQUE, "");
            $event->statUsed = $this->globals->get(GAME::CHALLENGE_STAT);
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stHighDramaChallengeActionResolution()
    {
        $this->globals->set(GAME::PASS_COUNT, 0);

        if ($this->globals->get(GAME::CHALLENGE_ACCEPTED))
        {
            $this->gamestate->nextState("accepted");
        }
        else
        {
            //Challenge was rejected, wound the target by the threat value.  Limit amount done by Restricted Hostilities.
            $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
            $performer = $this->getCardObjectFromDb($performerId);
            $targetId = $this->globals->get(GAME::CHOSEN_TARGET);
            $target = $this->getCardObjectFromDb($targetId);

            $threat = $this->globals->get(GAME::CHALLENGE_THREAT);

            $combatStatUsed = $this->globals->get(GAME::CHALLENGE_STAT);

            $stat = $performer->ModifiedCombat;
            $reason = "<p>";
            switch ($combatStatUsed)
            {
                case GAME::STAT_COMBAT:
                    $stat = $performer->ModifiedCombat;
                    $reason .= $this->translate("Stat Used for Challenge was Combat.");
                    break;
                case GAME::STAT_FINESSE:
                    $stat = $performer->ModifiedFinesse;
                    $reason .= $this->translate("Stat Used for Challenge was Finesse.");
                    break;
                case GAME::STAT_INFLUENCE:
                    $stat = $performer->ModifiedInfluence;
                    $reason .= $this->translate("Stat Used for Challenge was Influence.");
                    break;
            }

            $wounds = $threat;
            $reason .= "<p>" . $this->translate("Challenge was Rejected. Generated Threat was ") . $threat . ".";
            if ($threat > $stat)
            {
                $wounds = $stat;
                $reduction = $threat - $stat;
                $reason .= "<p>" . $this->translate("Threat was reduced by ") . $reduction . " due to Restricted Hostilities (Stat value of " . $stat . "). ";
            }

            $event = $this->theah->createEvent(Events::CharacterWounded);
            if ($event instanceof EventCharacterWounded)
            {
                $event->characterId = $target->Id;
                $event->sourceId = $performer->Id;
                $event->wounds = $wounds;
                $event->reason = $reason;
            }
            $this->theah->queueEvent($event);

            //Find out who the next player is in order
            $table = $this->getNextPlayerTable();
            $nextPlayerId = $table[$performer->ControllerId];

            $nextPlayerEvent = EventFactory::createChangeActivePlayerEvent($nextPlayerId);
            $this->theah->queueEvent($nextPlayerEvent);

            $this->gamestate->nextState("rejected");
        }
    }

    public function stDuelStarted()
    {
        $duelId = $this->globals->get(GAME::DUEL_ID) + 1;
        $this->globals->set(GAME::DUEL_ID, $duelId);
        
        $this->globals->set(GAME::IN_DUEL, true);
        $this->globals->set(GAME::DUEL_TYPE, GAME::NORMAL_DUEL_TYPE);
        
        $challengerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $challenger = $this->getCardObjectFromDb($challengerId);
        $defenderId = $this->globals->get(GAME::CHOSEN_TARGET);
        $defender = $this->getCardObjectFromDb($defenderId);
        $threat = $this->globals->get(GAME::CHALLENGE_THREAT);
        
        $sql = "INSERT INTO duel (duel_id, challenging_player_id, challenger_id, defending_player_id, defender_id, challenger_threat, defender_threat) 
        VALUES ($duelId, {$challenger->ControllerId}, $challengerId, {$defender->ControllerId}, $defenderId, 0, $threat)";
        $this->DbQuery($sql);
        
        $this->notifyAllPlayers("duelStarted", clienttranslate('A DUEL HAS STARTED.'), [
            "duelId" => $duelId,
            "challengerId" => $challengerId,
            "challengingPlayerId" => $challenger->ControllerId,
            "defenderId" => $defenderId,
            "defendingPlayerId" => $defender->ControllerId,
            "threat" => $threat
        ]);

        $event = $this->theah->createEvent(Events::DuelStarted);
        if ($event instanceof EventDuelStarted)
        {
            $event->challengerId = $challengerId;
            $event->defenderId = $defenderId;
        }
        $this->theah->queueEvent($event);
        
        $this->gamestate->nextState();
    }

    public function stDuelNewRound()
    {
        $duelId = $this->globals->get(GAME::DUEL_ID);
        $round = 1;
        
        if ( ! $this->globals->has(GAME::DUEL_ROUND))
            $this->globals->set(GAME::DUEL_ROUND, $round);
        else
        {
            $round = $this->globals->get(GAME::DUEL_ROUND) + 1;
            $this->globals->set(GAME::DUEL_ROUND, $round);
        }

        $sql = "SELECT challenging_player_id, challenger_id, challenger_threat, defending_player_id, defender_id, defender_threat FROM duel WHERE duel_id = {$duelId}";
        $result = $this->getObjectListFromDb($sql)[0];
        $challengingPlayerId = $result['challenging_player_id'];
        $challengerId = $result['challenger_id'];
        $challengerThreat = $result['challenger_threat'];
        
        $defendingPlayerId = $result['defending_player_id'];
        $defenderId = $result['defender_id'];
        $defenderThreat = $result['defender_threat'];
        
        $challenger = $this->getCardObjectFromDb($challengerId);
        $defender = $this->getCardObjectFromDb($defenderId);
        $wounds = $defenderThreat;

        //If the first round, then the defender is the active player
        $actorId = 0;
        $actor = null;
        $playerId = 0;
        if ($round == 1)
        {
            $actorId = $defenderId;
            $actor = $defender;
            $playerId = $defendingPlayerId;
        }
        else
        {
            //Get the actor from the previous round
            $sql = "SELECT actor_id, ending_challenger_threat, ending_defender_threat FROM duel_round WHERE duel_id = {$duelId} AND round = " . ($round - 1);
            $result = $this->getObjectListFromDB($sql)[0];
            $lastActorId = $result['actor_id'];
            if ($lastActorId == $challengerId)
            {
                $actorId = $defenderId;
                $actor = $defender;
                $playerId = $defendingPlayerId;
                $challengerThreat = 0;
                $defenderThreat = $result['ending_defender_threat'];
                $wounds = $defenderThreat;
            }
            else
            {
                $actorId = $challengerId;
                $actor = $challenger;
                $playerId = $challengingPlayerId;
                $challengerThreat = $result['ending_challenger_threat'];
                $defenderThreat = 0;
                $wounds = $challengerThreat;
            }
        }

        $serialized = addslashes(serialize($actor));
        $sql = "INSERT INTO duel_round (duel_id, round, player_id, actor_id, actor_serialized, starting_challenger_threat, starting_defender_threat, ending_challenger_threat, ending_defender_threat, wounds_taken) 
        VALUES ($duelId, $round, $playerId, $actorId, '$serialized', $challengerThreat, $defenderThreat, $challengerThreat, $defenderThreat, $wounds)";
        $this->DbQuery($sql);

        $event = $this->theah->createEvent(Events::DuelNewRound);
        if ($event instanceof EventDuelNewRound)
        {
            $event->duelId = $duelId;
            $event->round = $round;
            $event->playerId = $playerId;
            $event->actorId = $actorId;
            $event->challengerThreat = $challengerThreat;
            $event->defenderThreat = $defenderThreat;
        }
        $this->theah->queueEvent($event);

        $playerName = $this->getPlayerNameById($playerId);
        $this->notifyAllPlayers("newDuelRound", clienttranslate('DUEL ROUND #${round} HAS STARTED for ${player_name} and their ${role} character <strong>${character_name}</strong>.'), [
            'i18n' => ['role', 'character_name', 'challengerName', 'defenderName'],
            "player_name" => $playerName,
            "role" => $round % 2 == 1 ? "Defending" : "Challenging",
            "character_name" => $actor->Name,
            "round" => $round,
            "playerId" => $playerId,
            "challengerId" => $challengerId,
            "defenderId" => $defenderId,
            "actorId" => $actorId,
            "actor" => $actor->getPropertyArray($this),
            "challengerName" => $challenger->Name,
            "defenderName" => $defender->Name,
            "startingChallengerThreat" => $challengerThreat,
            "startingDefenderThreat" => $defenderThreat,
            "endingChallengerThreat" => $challengerThreat,
            "endingDefenderThreat" => $defenderThreat,
            "wounds" => $wounds
            ]);    

        //Change to the active player based on the round number
        $changeEvent = EventFactory::createChangeActivePlayerEvent($playerId);
        $this->theah->queueEvent($changeEvent);

        $this->gamestate->nextState();
    }

    public function stApplyCombatCardStats(): void
    {
        $playerId = $this->getActivePlayerId();

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);

        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $adversaryId = $this->getDuelOpponentId($actorId);

        $cardId = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($cardId);

        $sql = "UPDATE duel_round SET combat_card_id = {$card->Id} WHERE duel_id = $duelId AND round = $round";
        $this->DbQuery($sql);

        $sql = "SELECT gambled from duel_round where duel_id = $duelId AND round = $round";
        $gambled = $this->getUniqueValueFromDB($sql);

        $event = $this->theah->createEvent(Events::DuelCalculateCombatCardStats);
        if ($event instanceof EventDuelCalculateCombatCardStats)
        {
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->combatCardId = $cardId;
            $event->riposte = $card->Riposte;
            $event->parry = $card->Parry;
            $event->thrust = $card->Thrust;
            $event->gambled = $gambled == 1;
        }
        $this->theah->queueEvent($event);
        $this->gamestate->nextState();
    }

    public function stDuelGetManeuverFromCombatCardCost(): void
    {
        $cardId = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($cardId);
        $cost = $card->WealthCost;

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $adversaryId = $this->getDuelOpponentId($actorId);

        $maneuverId = $this->globals->get(GAME::CHOSEN_MANEUVER);

        $event = $this->theah->createEvent(Events::DuelGetCostForManeuverFromHand);
        if ($event instanceof EventDuelGetCostForManeuverFromHand)
        {
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->combatCardId = $cardId;
            $event->maneuverId = $maneuverId;
            $event->cost = $cost;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stDuelEndOfRound(): void
    {
        $this->theah->buildCity();

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);

        $sql = "SELECT challenger_id, defender_id FROM duel where duel_id = $duelId";
        $result = $this->getObjectListFromDB($sql)[0];
        $challengerId = $result['challenger_id'];
        $defenderId = $result['defender_id'];

        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $values = $this->getObjectListFromDB($sql)[0];

        $actorId = $values['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);
        
        $adversaryId = $this->getDuelOpponentId($actorId);
        $adversary = $this->theah->getCharacterById($adversaryId);
        if (!$adversary)
            $adversary = $this->getCardObjectFromDb($adversaryId);

        //If the adversary is dead or not in the same location as the actor, then any adversary threat is nullified
        if ($actor->Location != $adversary->Location)
        {
            $field = "";
            if ($actorId == $challengerId)
                $field = "ending_defender_threat";
            else
                $field = "ending_challenger_threat";
            $sql = "UPDATE duel_round SET $field = 0 WHERE duel_id = $duelId AND round = $round";
            $this->DbQuery($sql);

            //Also make sure the threat read from the database is 0
            $values[$field] = 0;

            $this->notifyAllPlayers("message", clienttranslate('Due to the challenger and defender not sharing the same location, any threat from ${actor_name} to ${adversary_name} is nullified.'), [
                'i18n' => ['actor_name', 'adversary_name'],
                "actor_name" => $actor->Name,
                "adversary_name" => $adversary->Name
            ]);
        }
        
        //Any threat remaining for the actor is applied
        $threat = 0;
        $field = "";
        if ($actorId == $challengerId)
        {
            $threat = $values['ending_challenger_threat'];
            $field = "ending_challenger_threat";
        }
        else
        {
            $threat = $values['ending_defender_threat'];
            $field = "ending_defender_threat";
        }
        $sql = "UPDATE duel_round SET $field = 0 WHERE duel_id = $duelId AND round = $round";
        $this->DbQuery($sql);

        if ($threat > 0)
        {
            $combatStatUsed = $this->globals->get(GAME::CHALLENGE_STAT);

            $stat = $adversary->ModifiedCombat;
            $reason = "<p>$threat " . $this->translate("Threat was left over in their Pool.");

            $wounds = $threat;
            if ($threat > $stat)
            {
                switch ($combatStatUsed)
                {
                    case GAME::STAT_COMBAT:
                        $stat = $adversary->ModifiedCombat;
                        $reason .= "<p>" . $this->translate("Stat Used for Duel is Combat.");
                        break;
                    case GAME::STAT_FINESSE:
                        $stat = $adversary->ModifiedFinesse;
                        $reason .= "<p>" . $this->translate("Stat Used for Duel is Finesse.");
                        break;
                    case GAME::STAT_INFLUENCE:
                        $stat = $adversary->ModifiedInfluence;
                        $reason .= "<p>" . $this->translate("Stat Used for Duel is Influence.");
                        break;
                }
                $wounds = $stat;
                $reduction = $threat - $stat;
                $reason .= "<p>" . $this->translate("Wounds were reduced by ") . $reduction . " due to Restricted Hostilities (Stat value of " . $stat . "). ";
            }

            $event = $this->theah->createEvent(Events::CharacterWounded);
            if ($event instanceof EventCharacterWounded)
            {
                $event->characterId = $actor->Id;
                $event->sourceId = $adversary->Id;
                $event->wounds = $wounds;
                $event->reason = $reason;
            }
            $this->theah->queueEvent($event);
        }

        $this->globals->delete(GAME::CHOSEN_TECHNIQUE);
        $this->globals->delete(GAME::CHOSEN_MANEUVER);
        $this->globals->delete(GAME::CHOSEN_CARD);
        $this->globals->delete(GAME::CHOSEN_CARD_COST);
        $this->globals->delete(GAME::DISCOUNT);

        $this->gamestate->nextState();
    }

    public function stDuelNextPlayer(): void
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);    

        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $values = $this->getObjectListFromDB($sql)[0];
        $actorId = $values['actor_id'];
        $endingChallengerThreat = $values['ending_challenger_threat'];
        $endingDefenderThreat = $values['ending_defender_threat'];

        if ($endingChallengerThreat == 0 && $endingDefenderThreat == 0)
        {
            $this->gamestate->nextState("endOfDuel");
            return;
        }

        $sql = "SELECT challenging_player_id, challenger_id, defending_player_id, defender_id FROM duel where duel_id = $duelId";
        $result = $this->getObjectListFromDB($sql)[0];
        $challengingPlayerId = $result['challenging_player_id'];
        $challengerId = $result['challenger_id'];
        $defendingPlayerId = $result['defending_player_id'];

        // Change to the next player in the duel
        if ($actorId == $challengerId)
            $this->gamestate->changeActivePlayer($defendingPlayerId);
        else
            $this->gamestate->changeActivePlayer($challengingPlayerId);

        $this->gamestate->nextState("newRound");

    }

    public function stDuelEnd(): void
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $this->globals->set(GAME::IN_DUEL, false);

        $this->globals->delete(Game::CHALLENGE_STAT);
        $this->globals->delete(Game::CHALLENGE_THREAT);
        $this->globals->delete(Game::CHALLENGE_ACCEPTED);
        $this->globals->delete(Game::DUEL_ID);
        $this->globals->delete(Game::DUEL_ROUND);
        $this->globals->delete(Game::DUEL_CHALLENGER);
        $this->globals->delete(Game::DUEL_DEFENDER);

        $sql = "SELECT challenging_player_id, defending_player_id, challenger_id, defender_id FROM duel where duel_id = $duelId";
        $result = $this->getObjectListFromDB($sql)[0];

        $event = $this->theah->createEvent(Events::DuelEnd);
        if ($event instanceof EventDuelEnd)
        {
            $event->challengingPlayerId = $result['challenging_player_id'];
            $event->defendingPlayerId = $result['defending_player_id'];
            $event->challengerId = $result['challenger_id'];
            $event->defenderId = $result['defender_id'];
        }
        $this->theah->queueEvent($event);

        //Get all the cards in purgatory and move them to the discard pile
        $cards = $this->cards->getCardsInLocation(Game::LOCATION_PURGATORY);
        foreach ($cards as $purgatoryCard)
        {
            $card = $this->getCardObjectFromDb($purgatoryCard['id']);
            $playerId = $card->ControllerId;
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
            $this->theah->queueEvent($event);
        }

        $changeEvent = EventFactory::createChangeActivePlayerEvent($result['challenging_player_id']);
        $this->theah->queueEvent($changeEvent);

        $this->gamestate->nextState();
    }

    public function stNextPlayer(): void 
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();
        $this->giveExtraTime($player_id);

        // Clear the player action globals
        $this->globals->delete(GAME::CHOSEN_CARD);
        $this->globals->delete(GAME::CHOSEN_CARD_COST);
        $this->globals->delete(GAME::CHOSEN_LOCATION);
        $this->globals->delete(GAME::CHOSEN_PERFORMER);
        $this->globals->delete(GAME::CHOSEN_ACTION);
        $this->globals->delete(GAME::CHOSEN_TARGET);
        $this->globals->delete(GAME::CHOSEN_TECHNIQUE);
        $this->globals->delete(GAME::CHOSEN_MANEUVER);
        $this->globals->delete(Game::TRANSITION_SOURCE_ID);
        $this->globals->delete(Game::TRANSITION_INTERNAL_ID);
        $this->globals->delete(Game::REACTION_ID);
        $this->globals->set(Game::CLAIM_TYPE, Game::NORMAL_CLAIM_TYPE);
        $this->globals->set(Game::EQUIP_TYPE, Game::NORMAL_EQUIP_TYPE);

        $currentPlayerId = $this->globals->get(Game::CURRENT_PLAYER);
        $nextPlayerId = $this->getPlayerAfter($currentPlayerId);
        $this->gamestate->changeActivePlayer($nextPlayerId);
        $this->globals->set(Game::CURRENT_PLAYER, $nextPlayerId);
        
        $this->gamestate->nextState("nextPlayer");
    }

    public function stHighDramaEnd(): void
    {
        $event = $this->theah->createEvent(Events::HighDramaPhaseEnd);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stPlunderPhaseBegin(): void
    {
         //Set the phase
         $this->setGameStateValue(Game::TURN_PHASE, Game::PLUNDER);

         $event = $this->theah->createEvent(Events::PlunderPhaseBegin);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stPlunderCheckDominanceVictory(): void
    {
        //Check for dominance victory
        $controllerForDocks = $this->getControllerForLocation(Game::LOCATION_CITY_DOCKS);
        $controllerForForum = $this->getControllerForLocation(Game::LOCATION_CITY_FORUM);
        $controllerForBazaar = $this->getControllerForLocation(Game::LOCATION_CITY_BAZAAR);

        //If all the same player controls all three locations, then they win
        if ($controllerForDocks != 0 && $controllerForDocks == $controllerForForum && $controllerForForum == $controllerForBazaar)
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved a DOMINANCE VICTORY by controlling all three core locations.'), [
                "player_name" => $this->getPlayerNameById($controllerForDocks)
            ]);

            $players = $this->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player)
            {
                if ($playerId != $controllerForDocks)
                    $this->setPlayerReknown($playerId, -1);               
            }
            
            $this->gamestate->nextState("endOfGame");
        }

        $this->notifyAllPlayers("message", clienttranslate('No player has achieved a DOMINANCE VICTORY by controlling all three core locations.'), []);
        $this->gamestate->nextState("next");
    }

    public function stPlunderGainReknown(): void
    {
        $this->theah->buildCity();

        //Get locations that have a controller
        $locations = array_filter($this->theah->getCityLocations(), fn($location) => $location->Controller != 0);

        foreach ($locations as $location)
        {
            $event = $this->theah->createEvent(Events::PlayerTakeReknownForControlledLocation);
            if ($event instanceof EventPlayerTakeReknownForControlledLocation)
            {
                $event->playerId = $location->Controller;
                $event->location = $location->Name;
                $event->reknown = $location->Reknown;
            }
            $this->theah->queueEvent($event);

            $event = EventFactory::createPlayerGainsReknownEvent($location->Controller, $location->Reknown);
            $this->theah->queueEvent($event);

            $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
            if ($event instanceof EventReknownRemovedFromLocation) {
                $event->location = $location->Name;
                $event->amount = $location->Reknown;
                $event->source = "Location Claimed";
            }
            $this->theah->queueEvent($event);
    }

        $event = $this->theah->createEvent(Events::PlunderPhaseAdditionalReknownEvent);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stPlunderCheckEconomicVictory(): void
    {
        $players = $this->loadPlayersBasicInfos();
        $winners = [];        
        foreach ($players as $playerId => $player)
        {
            $reknown = $this->getPlayerReknown($playerId);
            if ($reknown >= 7)
            {
                $winners[] = $playerId;
            }
        }

        if (count($winners) == 0)
        {
            $this->notifyAllPlayers("message", clienttranslate('No player has achieved an ECONOMIC VICTORY by gaining 7 or more reknown.'), []);
        }
        else if (count($winners) == 1)
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved an ECONOMIC VICTORY by gaining 7 or more reknown.'), [
                "player_name" => $this->getPlayerNameById($winners[0])
            ]);

            $this->gamestate->nextState("endOfGame");
            return;
        }
        else if (count($winners) > 1)
        {
            $day = $this->getGameStateValue("day");
            if ($day < 5)
            {
                $this->notifyAllPlayers("message", clienttranslate('Multiple players have achieved an ECONOMIC VICTORY by gaining 7 or more reknown. Another day will be played.'), []);
                $this->gamestate->nextState("endOfGame");
                return;
            }
        }

        $this->gamestate->nextState("next");
    }

    public function stPlunderCheckFifthDayVictory(): void
    {
        $this->theah->buildCity();
        $day = $this->getGameStateValue("day");
        if ($day == 5)
        {
            $this->notifyAllPlayers("message", clienttranslate('IT IS THE END OF THE FIFTH DAY.'), []);

            $players = $this->loadPlayersBasicInfos();
            $highestReknown = -1;
            $highestReknownPlayer = 0;
            foreach ($players as $playerId => $player)
            {
                $reknown = $this->getPlayerReknown($playerId);
                if ($reknown > $highestReknown)
                {
                    $highestReknown = $reknown;
                    $highestReknownPlayer = $playerId;
                }
            }

            //How check to see if there are any ties
            $reknownWinners = [];        
            foreach ($players as $playerId => $player)
            {
                $reknown = $this->getPlayerReknown($playerId);
                if ($reknown == $highestReknown)
                {
                    $reknownWinners[] = $playerId;
                }
            }

            if (count($reknownWinners) == 1)
            {
                $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved a VICTORY by having the most reknown.'), [
                    "player_name" => $this->getPlayerNameById($highestReknownPlayer)
                ]);

                $this->gamestate->nextState("endOfGame");
                return;
            }

            $playerList = "";
            foreach ($reknownWinners as $playerId)
                $playerList .= "<p>" . $this->getPlayerNameById($playerId);
            $this->notifyAllPlayers("message", clienttranslate('There is a tie amongst the players with the most Reknown of ${reknown}. Tied players: ${tied_players}
            <p>Number of controlled locations will now be counted to break the tie.'), [
                "reknown" => $highestReknown,
                "tied_players" => $playerList
            ]);

            //We are in a tie situation.  Check to see who controls the most locations
            $locations = array_filter($this->theah->getCityLocations(), fn($location) => $location->Controller != 0);
            $locationCounts = [];
            foreach ($locations as $location)
            {
                if (array_key_exists($location->Controller, $locationCounts))
                    $locationCounts[$location->Controller]++;
                else
                    $locationCounts[$location->Controller] = 1;
            }

            $highestCount = 0;
            $highestCountPlayer = 0;
            //Go through the winners array, which contains the playerIds of the players with the highest reknown, 
            //and see who controls the most locations
            foreach ($reknownWinners as $playerId)
            {
                if (array_key_exists($playerId, $locationCounts))
                {
                    $count = $locationCounts[$playerId];

                    $this->notifyAllPlayers("message", clienttranslate('${player_name} controls ${count} locations.'), [
                        "player_name" => $this->getPlayerNameById($playerId),
                        "count" => $count
                    ]);

                    //Formula for auxiliary score (to break ties) is 1000 * number of locations controlled
                    $auxScore = $this->dbGetAuxScore($playerId);
                    $auxScore += $count * 1000;
                    $this->dbSetAuxScore($playerId, $auxScore);
                }
                else
                {
                    $count = 0;
                    $this->notifyAllPlayers("message", clienttranslate('${player_name} controls NO locations.'), [
                        "player_name" => $this->getPlayerNameById($playerId),
                    ]);
                }

                if ($count > $highestCount)
                {
                    $highestCount = $count;
                    $highestCountPlayer = $playerId;
                }
            }

            //See if there are any ties in the location count
            $locationWinners = [];
            foreach ($reknownWinners as $playerId)
            {
                if (array_key_exists($playerId, $locationCounts) && $locationCounts[$playerId] == $highestCount)
                {
                    $locationWinners[] = $playerId;
                }
            }

            if (count($locationWinners) == 1)
            {
                $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved a VICTORY by controlling the most locations.'), [
                    "player_name" => $this->getPlayerNameById($highestCountPlayer)
                ]);

                $this->gamestate->nextState("endOfGame");
                return;
            }

            //Just in case there were no locations controlled by any of the players we will re-use the renown winners
            if (count($locationWinners) == 0)
                $locationWinners = $reknownWinners;
            
            $playerList = "";
            foreach ($locationWinners as $playerId)
                $playerList .= "<p>" . $this->getPlayerNameById($playerId);
            $this->notifyAllPlayers("message", clienttranslate('There is a tie amongst the players controlling the most locations (${locations}). Tied players: ${tied_players}
            <p>The player with the most Influence will be used to break the tie.'), [
                "locations" => $highestCount,
                "tied_players" => $playerList
            ]);

            //We still have a tie.  Now find the player with the most influence
            $highestInfluence = -1;
            $highestInfluencePlayer = 0;
            foreach ($locationWinners as $playerId)
            {
                $influence = $this->theah->getTotalPlayerInfluence($playerId);

                $this->notifyAllPlayers("message", clienttranslate('${player_name} controls ${influence} Influence.'), [
                    "player_name" => $this->getPlayerNameById($playerId),
                    "influence" => $influence
                ]);

                //Formula for auxiliary score (to break ties) is 100 * total influence
                $auxScore = $this->dbGetAuxScore($playerId);
                $auxScore += $influence * 100;
                $this->dbSetAuxScore($playerId, $auxScore);

                if ($influence > $highestInfluence)
                {
                    $highestInfluence = $influence;
                    $highestInfluencePlayer = $playerId;
                }
            }

            $influenceWinners = [];
            foreach ($locationWinners as $playerId)
            {
                $influence = $this->theah->getTotalPlayerInfluence($playerId);
                if ($influence == $highestInfluence)
                {
                    $influenceWinners[] = $playerId;
                }
            }

            if (count($influenceWinners) == 1)
            {
                $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved a VICTORY by controlling the most Influence.'), [
                    "player_name" => $this->getPlayerNameById($highestInfluencePlayer)
                ]);

                $this->gamestate->nextState("endOfGame");
                return;
            }

            $playerList = "";
            foreach ($influenceWinners as $playerId)
                $playerList .= "<p>" . $this->getPlayerNameById($playerId);
            $this->notifyAllPlayers("message", clienttranslate('There is a tie amongst the players with highest total Influence (${influence}). Tied players: ${tied_players}
            <p>The Leader with the least wounds will be used to break the tie.'), [
                "influence" => $highestInfluence,
                "tied_players" => $playerList
            ]);

            //We still have a tie. The leader with the least wounds breaks the tie
            $lowestWounds = 1000;
            $lowestWoundsPlayer = 0;

            foreach ($influenceWinners as $playerId)
            {
                $leader = $this->theah->getLeaderByPlayerId($playerId);

                $this->notifyAllPlayers("message", clienttranslate('${player_name}:<strong>${leader_name}</strong> has ${wounds} Wounds.'), [
                    "player_name" => $this->getPlayerNameById($playerId),
                    "leader_name" => $leader->Name,
                    "wounds" => $leader->Wounds
                ]);

                //Formula for auxiliary score (to break ties) is 20 - wounds
                $auxScore = $this->dbGetAuxScore($playerId);
                $auxScore += 20 - $leader->Wounds;
                $this->dbSetAuxScore($playerId, $auxScore);

                if ($leader->Wounds < $lowestWounds)
                {
                    $lowestWounds = $leader->Wounds;
                    $lowestWoundsPlayer = $playerId;
                }
            }

            $woundsWinners = [];
            foreach ($influenceWinners as $playerId)
            {
                $leader = $this->theah->getLeaderByPlayerId($playerId);
                if ($leader->Wounds == $lowestWounds)
                {
                    $woundsWinners[] = $playerId;
                }
            }

            if (count($woundsWinners) == 1)
            {
                $this->notifyAllPlayers("message", clienttranslate('${player_name} has achieved a VICTORY by having their Leader have the least Wounds.'), [
                    "player_name" => $this->getPlayerNameById($lowestWoundsPlayer)
                ]);
            }            

            $this->gamestate->nextState("endOfGame");
        }

        $this->gamestate->nextState("next");
    }

    public function stPlunderPhaseEnd(): void
    {
        $event = $this->theah->createEvent(Events::PlunderPhaseEnd);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stDuskPhaseBegin(): void
    {
        //Set the phase
        $this->setGameStateValue(Game::TURN_PHASE, Game::DUSK);

        $event = $this->theah->createEvent(Events::DuskPhaseBegin);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stDuskPhaseCleanup(): void
    {
        $this->theah->buildCity();

        //All locations reset
        $locations = $this->theah->getCityLocations();        
        foreach ($locations as $location)
        {
            $this->setControllerForLocation($location->Name, 0);
            $location->Controller = 0;
        }

        //Get characters in play
        $characters = $this->theah->getCharactersInPlay();

        //Only use characters that are in the city
        $characters = array_filter($characters, fn($character) => $this->theah->cardInCity($character));

        //Only use characters that are controlled (i.e. not mercenaries)
        $characters = array_filter($characters, fn($character) => $character->ControllerId != 0);

        foreach ($characters as $character)
        {
            $movedHome = EventFactory::createCardMovedEvent($character->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false);
            $this->theah->queueEvent($movedHome);

            if ($character->Engaged)
            {
                $engardeEvent = $this->theah->createEvent(Events::CardEngarded);
                if ($engardeEvent instanceof EventCardEngarded)
                {
                    $engardeEvent->cardId = $character->Id;
                    $engardeEvent->playerId = $character->ControllerId;
                }
                $this->theah->queueEvent($engardeEvent);                    
            }
        }

        //Discard all city cards in the city that are not controlled
        foreach ($locations as $location)
        {
            $cards = $this->theah->getCardObjectsAtLocation($location->Name);
            foreach ($cards as $card)
            {
                if ($card instanceof ICityDeckCard && $card->ControllerId == 0)
                {
                    $discard = $this->theah->createEvent(Events::CardAddedToCityDiscardPile);
                    if ($discard instanceof EventCardAddedToCityDiscardPile)
                    {
                        $discard->cardId = $card->Id;
                        $discard->fromLocation = $location->Name;
                    }    
                    $this->theah->queueEvent($discard);
                }
            }
        }

        //Send all schemes to the locker
        $players = $this->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            $sql = "SELECT selected_scheme_id as id FROM player where player_id = $playerId";
            $schemeId = $this->getUniqueValueFromDB($sql);

            $sql = "UPDATE player SET selected_scheme_id = NULL, selected_character_id = NULL WHERE player_id = $playerId";
            $this->DbQuery($sql);

            $locker = $this->getPlayerLockerName($playerId);
            $this->cards->moveCard($schemeId, $locker);

            $event = $this->theah->createEvent(Events::SchemeSentToLocker);
            if ($event instanceof EventSchemeSentToLocker)
            {
                $event->schemeId = $schemeId;
            }
            $this->theah->queueEvent($event);

        }

        $this->gamestate->nextState();
    }

    public function stDuskPhaseDiscard(): void
    {
        $playersToDiscard = [];
        $sql = "SELECT player_id, leader_card_id as leaderId FROM player";
        $players = $this->getCollectionFromDB($sql);
        foreach ($players as $playerId => $player)
        {
            $leader = $this->getCardObjectFromDb($player['leaderId']);
            $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
            if (count($hand) > $leader->Panache)
            {
                $playersToDiscard[] = $playerId;
            }
        }

        if (count($playersToDiscard) == 0)
            $this->notifyAllPlayers("message", clienttranslate('No players need to discard down to their Leader\'s Panache value.'), []);
        else
            $this->notifyAllPlayers("message", clienttranslate('The following players need to discard down to their Leader\'s Panache value: ${players}.'), [
                "players" => implode(", ", array_map(fn($playerId) => $this->getPlayerNameById($playerId), $playersToDiscard))
            ]);

        $this->gamestate->setPlayersMultiactive($playersToDiscard, "cardsDiscarded");
    }

    public function stDuskPhaseDiscardEvents(): void
    {
        //Get all the cards in purgatory and move them to the discard pile
        $cards = $this->cards->getCardsInLocation(Game::LOCATION_PURGATORY);
        foreach ($cards as $purgatoryCard)
        {
            $card = $this->getCardObjectFromDb($purgatoryCard['id']);
            $playerId = $card->ControllerId;
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
            $this->theah->queueEvent($event);
        }

        $this->theah->buildCity();
        $this->theah->runEvents();
    }

    public function stDuskPhaseEnd(): void
    {
        $event = $this->theah->createEvent(Events::DuskPhaseEnd);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stDuskEndOfDay(): void
    {
        $event = $this->theah->createEvent(Events::DuskEndOfDay);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState();
    }

    public function stEndGame(): void
    {

    }
}