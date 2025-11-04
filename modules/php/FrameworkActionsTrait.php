<?php

/**
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See https://en.doc.boardgamearena.com/Studio for more information.
 */

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelPlayerGambled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

trait FrameworkActionsTrait
{
    public function actPass(string $transition = ""): void
    {
        // Retrieve the active player ID.
        $playerId = $this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("message", clienttranslate('${player_name} passes.'), [
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState($transition);
    }

    public function actPassWithPass(): void
    {
        $this->actPass("pass");
    }

    public function actHighDramaPass(): void
    {
        $playerId = $this->getActivePlayerId();

        $event = $this->theah->createEvent(Events::HighDramaPhasePlayerPassed);
        if ($event instanceof EventHighDramaPhasePlayerPassed) {
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $passCount = $this->globals->get(GAME::PASS_COUNT, 0);
        $passCount++;        
        $this->globals->set(GAME::PASS_COUNT, $passCount);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $this->theah->queueEvent($actionResolvedEvent);

        if ($passCount >= $this->globals->get(GAME::PLAYER_COUNT)) 
            $this->gamestate->nextState("end");
        else
            $this->gamestate->nextState("pass");
    }

    public function actBack(): void
    {
        $this->gamestate->nextState("back");
    }

    public function actBackWithTransition(string $transition): void
    {
        $this->gamestate->nextState($transition);
    }

    public function actMultipleOk(): void{
        $playerId = $this->getCurrentPlayerId();
        $this->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
    }
    
    public function actPickDeck(string $deck_type, string $deck_id, string $deck_json): void
    {
        $playerId = $this->getCurrentPlayerId();

        if ($deck_type === 'starter') 
        {
            $starter_decks = json_decode(StarterDecks::$decksJson);
            $deck = current(array_filter($starter_decks->decks, fn($deck) => $deck->id === $deck_id));
            if (! $deck)
            {
                throw new \BgaUserException(sprintf(self::_("%s is not a starter valid deck."), $deck_id));
            }

            $this->notifyPlayer($playerId, 'message', clienttranslate('Private: You have chosen ${deck_name} as your Starter Deck.'), [
                'deck_name' => $deck->name,
            ]);

            $deck_json = addslashes(json_encode($deck));
        }

        $sql = "UPDATE player SET deck_source = '$deck_json' WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actDayPlanned(int $scheme, int $character): void
    {
        $playerId = $this->getCurrentPlayerId();
        $sql = "UPDATE player SET selected_scheme_id = '$scheme', selected_character_id = '$character'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        //Move the cards to a purgatory state while waiting for the other players to finish their day planning.
        //This is necessary to prevent the card from being shown back in the player's approach deck if they F5.
        if ($scheme)
            $this->cards->moveCard($scheme, Game::LOCATION_PURGATORY);
        if ($character)
            $this->cards->moveCard($character, Game::LOCATION_PURGATORY);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned'); // deactivate player; if none left, transition to 'dayPlanned' state
    }

    public function actCityLocationsForReknownSelected(string $locations)
    {
        $locations = json_decode($locations, true);

        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->playerId = $this->getActivePlayerId();
                $event->location = $location;
                $event->amount = 1;
            }
            $this->theah->eventCheck($event);
        }

        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->playerId = $this->getActivePlayerId();
                $event->location = $location;
                $event->amount = 1;
            }
            $this->theah->queueEvent($event);
        }

        // Go back and finish running the Scheme events
        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125(string $locations)
    {
        $location = json_decode($locations, true)[0];
        
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->description = "The Boar's Guile: Adding Renown to Location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to place renown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);

        $this->gamestate->nextState("reknownPlaced");
    }

    public function actPlanningPhase_01125_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to pass placing renown onto a location.  Per The Boar\'s Guile you will now choose a city location to move a Renown FROM.'), []);

        $this->gamestate->nextState("pass");
    }

    public function actPlanningPhase_01125_2(string $locations)
    {
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $reknown = $this->getReknownForLocation($location);
        if ($reknown <= 0) 
            throw new \BgaUserException(sprintf(self::_("%s does not have any renown to move."), $location));
        
        $event = EventFactory::createReknownRemovedFromLocationEvent($this->getActivePlayerId(), $location, 1, "The Boar's Guile: Moving Renown from one Location to an adjacent location");
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to move renown from ${location}.  You must now choose a location to move the Renown TO.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);
        
        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        $this->gamestate->nextState("locationChosen");
    }

    public function actPlanningPhase_01125_2_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have passed choosing a location to move renown from.  Per The Boar\'s Guile you must now choose an enemy character to target.'), []);

        $this->gamestate->nextState("pass");
    }

    public function actPlanningPhase_01125_3(string $locations)
    {
        $location = json_decode($locations, true)[0];

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->description = "The Boar's Guile: Moving Renown from one Location to an adjacent location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to move renown to ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_4(int $id)
    {
        $playerName = $this->getActivePlayerName();
        $character = $this->getCardObjectFromDb($id);

        $this->notifyAllPlayers('yevgeniAdversaryChosen', 
            clienttranslate('${player_name} has chosen ${character_inject_code} as Yevgeni\'s Adversary.'), [
            "player_name" => $playerName,
            "character_inject_code" => $character->getInjectCode(),
            "cardId" => $character->Id,
        ]);

        $character->addCondition(Game::ADVERSARY_OF_YEVGENI);
        $this->updateCardObjectInDb($character);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_4_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have passed choosing a character as an adversary.'), []);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01126_2(string $leshiyeLocation, string $locations)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $locations = json_decode($locations, true);

        //Check to be sure location can be added to locations
        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->playerId = $this->getActivePlayerId();
                $event->location = $location;
                $event->amount = 1;
                $event->description = "Leshiye of the Wood: Adding Renown to Location";
            }
            $this->theah->eventCheck($event);        
        }

        //Get the chosen scheme card for the player
        $scheme = $this->getPlayerChosenScheme($playerId);

        //Check if event can be run
        $schemeMoveEvent = $this->theah->createEvent(Events::SchemeMovedToCity);
        if ($schemeMoveEvent instanceof EventSchemeMovedToCity) {
            $schemeMoveEvent->scheme = $scheme;
            $schemeMoveEvent->location = $leshiyeLocation;
            $schemeMoveEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($schemeMoveEvent);

        $this->notifyAllPlayers('message', 
            clienttranslate('${player_name} has chosen ${location} as the Chosen Location for ${scheme_inject_code}.'), [
            'i18n' => ['location'],
            "player_name" => $playerName,
            "location" => $leshiyeLocation,
            "scheme_inject_code" => $scheme->getInjectCode(),
        ]);


        if ($scheme instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01126) {
            $scheme->chosenLocation = $leshiyeLocation;
            $this->updateCardObjectInDb($scheme);
        }

        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->playerId = $this->getActivePlayerId();
                $event->location = $location;
                $event->amount = 1;
                $event->description = "Leshiye of the Wood: Adding Reknown to Location";
            }
            $this->theah->eventCheck($event);
            $this->theah->queueEvent($event);
        }

        // Move Leshiye of the Wood to the chosen location
        $this->cards->moveCard($scheme->Id, $leshiyeLocation, $playerId);
        $this->theah->queueEvent($schemeMoveEvent);

        // Go back and finish running the Scheme events
        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01144(string $locations)
    {
        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $activePlayerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->description = $playerName;
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        // Get all the reknown to compare
        $players = $this->getObjectListFromDb("SELECT player_id, player_score score FROM player ORDER BY player_score DESC");
        if (count($players) == 1) {
            $this->gamestate->nextState("fewestReknown");                
            return;
        }

        if ($players[0]['player_id'] != $activePlayerId) {
            $this->gamestate->nextState("notFewestReknown");                
            return;
        }

        if ($players[0]['score'] == $players[1]['score']) {
            $this->gamestate->nextState("notFewestReknown");                
            return;
        }

        $this->gamestate->nextState("fewestReknown");
    }

    public function actPlanningPhase_01144_2(string $locations)
    {
        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $playerName = $this->getActivePlayerName();

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->description = $playerName;
        }

        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01145(string $fromLocation, string $toLocation)
    {
        $playerId = $this->getActivePlayerId();
        $scheme = $this->getPlayerChosenScheme($playerId);
        $scheme->planningPhaseAction($this, $fromLocation, $toLocation);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01145_Pass()
    {
        $playerId = $this->getActivePlayerId();
        $scheme = $this->getPlayerChosenScheme($playerId);
        $scheme->planningPhaseAction($this, 'Pass', 'Pass');

        $this->gamestate->nextState("");
    }

    private function actRecruitMercenary(int $recruitId, string $payWithCards)
    {
        $character = $this->getCardObjectFromDb($recruitId);
        if ($character == null)
        {
            throw new \BgaUserException(self::_("Character not found."));
        }
        if ( ! $character instanceof CityCharacter)
        {
            throw new \BgaUserException(self::_("Character is not a City Character."));
        }

        $discount = $this->globals->get(Game::DISCOUNT, 0); 
        $explanations = $this->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');

        $cost = $character->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $recruitType = $this->globals->get(Game::RECRUIT_TYPE);
        if ($recruitType == Game::CIRILO_RECRUIT_TYPE)
        {
            $cost = 1;
        }

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);

            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Mercenary is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $playerId = $this->getActivePlayerId();

        //Recruit the character
        $recruitCharacterEvent = $this->theah->createEvent(Events::CharacterRecruited);
        if ($recruitCharacterEvent instanceof EventCharacterRecruited) {
            $recruitCharacterEvent->characterId = $character->Id;
            $recruitCharacterEvent->playerId = $playerId;
            $recruitCharacterEvent->discount = $discount;
            $recruitCharacterEvent->cost = $cost;
            $recruitCharacterEvent->explanations = $explanations;
        }
        $this->theah->eventCheck($recruitCharacterEvent);
        $this->theah->queueEvent($recruitCharacterEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
            //No check needed
            $this->theah->queueEvent($event);
        }
    }

    public function actHighDramaBeginning_01144(int $recruitId, string $payWithCards)
    {
        $this->actRecruitMercenary($recruitId, $payWithCards);
        $this->gamestate->nextState("");
    }

    public function actHighDramaMoveActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanMove($player_id) == false) {
            throw new \BgaUserException(self::_("Moving is not allowed right now."));
        }

        $this->gamestate->nextState("moveActionStart");
    }

    public function actHighDramaMoveActionPerformerChosen(int $id)
    {
        $character = $this->getCardObjectFromDb($id);

        $this->globals->set(GAME::CHOSEN_CARD, $character->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaMoveActionDestinationChosen(string $locations)
    {
        $location = json_decode($locations, true)[0];
        $playerName = $this->getActivePlayerName();

        $cardId = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($cardId);       

        $this->notifyAllPlayers("message", clienttranslate('${player_name} performed a Move Action.'), [
        "player_name" => $playerName,
        ]);

        $movedEvent = EventFactory::createCardMovedEvent($card->ControllerId, $card->Id, $card->Location, $location, $card->Location != Game::LOCATION_PLAYER_HOME);
        $this->theah->eventCheck($movedEvent);
        $this->theah->queueEvent($movedEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($card->ControllerId);
        $this->theah->queueEvent($actionResolvedEvent);

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("destinationChosen");
    }

    public function actHighDramaRecruitActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanRecruit($player_id) == false) {
            throw new \BgaUserException(self::_("Recruiting is not allowed right now."));
        }

        $this->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);

        $this->gamestate->nextState("recruitActionStart");
    }

    public function actHighDramaRecruitActionPerformerChosen(int $id)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $performer = $this->theah->getCharacterById($id);

        if (!$this->theah->cardInCity($performer)) {
            throw new \BgaUserException(self::_("Character is not in the City."));
        }

        $characters = $this->theah->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanReruit = [];
        foreach ($characters as $character) {
            $charactersAtLocation = $this->theah->getCharactersAtLocation($character->Location, $includeUncontrolled = true);
            $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return $character->hasTrait("Mercenary"); });
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }
        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanReruit);
        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Character not in a state to recruit mercenaries."));
        }

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);
        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaRecruitActionParleyYes()
    {
        $this->theah->buildCity();
        $id = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $character = $this->theah->getCharacterById($id);

        //Set the discount for recruiting a mercenary.
        [$discount, $explanations] = $this->theah->getParleyDiscount($character, true);
        if ($discount != 0)
        $this->notify->player($character->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
            "explanations" => $explanations,
        ]);

        $this->globals->set(Game::DISCOUNT, $discount);
        $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);
        $this->globals->set(GAME::PERFORMER_PARLEYED, true);

        $this->gamestate->nextState("parleyChosen");
    }

    public function actHighDramaRecruitActionParleyNo()
    {
        $this->theah->buildCity();
        $id = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $character = $this->theah->getCharacterById($id);

        [$discount, $explanations] = $this->theah->getParleyDiscount($character, false);
        if ($discount != 0)
        $this->notify->player($character->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
            "explanations" => $explanations,
        ]);

        $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);
        $this->globals->set(Game::DISCOUNT, $discount);
        $this->globals->set(GAME::PERFORMER_PARLEYED, false);
        $this->gamestate->nextState("parleyChosen");
    }

    public function actHighDramaRecruitActionMercenaryChosen(int $id)
    {
        $this->theah->buildCity();
        $recruitId = $id;
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location, $includeUncontrolled = true);
        $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return $character->hasTrait("Mercenary"); });        
        $mercenaryIds = array_map(function($character) { return $character->Id; }, $mercenariesAtLocation);
        if (!in_array($recruitId, $mercenaryIds)) {
            throw new \BgaUserException(self::_("Chosen character is not a Mercenary at the Performer's Location."));
        }

        $this->globals->set(GAME::CHOSEN_CARD, $recruitId);

        $this->gamestate->nextState("mercenaryChosen");
    }

    public function actHighDramaRecruitActionPayForMercenary(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();
        $discount = $this->globals->get(Game::DISCOUNT);
        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $recruitId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->theah->getCharacterById($performerId);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} chose ${card_inject_code} to perform a Recruit Action.'), [
            "player_name" => $playerName,
            "card_inject_code" => $performer->getInjectCode(),
        ]);

        $performerParleyed = $this->globals->get(GAME::PERFORMER_PARLEYED, false);
        if ($performerParleyed)
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} chose to Parley with ${card_inject_code}.'), [
                "player_name" => $playerName,
                "card_inject_code" => $performer->getInjectCode(),
            ]);
            
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performer->Id);
            $this->theah->eventCheck($engageEvent);
            $this->theah->queueEvent($engageEvent);
        }

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $this->theah->queueEvent($actionResolvedEvent);

        $this->actRecruitMercenary($recruitId, $payWithCards);
        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("mercenaryPaidFor");
    }

    public function actHighDramaEquipActionStart()
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if (!$this->handHasAttachments($playerId) && !$this->theah->playerCanEquip($playerId)) {
            throw new \BgaUserException(self::_("Equipping is not allowed right now."));
        }

        $this->globals->set(Game::EQUIP_TYPE, Game::NORMAL_EQUIP_TYPE);
        $this->gamestate->nextState("equipActionStart");
    }

    public function actHighDramaEquipActionPerformerChosen(int $id)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $performer = $this->theah->getCharacterById($id);
        $handHasAttachments = $this->handHasAttachments($playerId);

        $characters = $this->theah->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanEquip = [];
        foreach ($characters as $character) {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0 || $handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }
        $charactersAtHome = $this->theah->getCharactersAtHome($playerId);
        foreach ($charactersAtHome as $character) {
            if ($handHasAttachments) {
                $charactersThatCanEquip[] = $character;
            }
        }

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanEquip);
        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Character cannot equip attachments."));
        }

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actSimpleTransition(string $transition)
    {
        $this->gamestate->nextState($transition);
    }

    public function actHighDramaEquipActionAttachmentFromHandSelected(int $id)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        $attachment = $this->getCardObjectFromDb($id);
        if ($attachment == null || $attachment->Location != Game::LOCATION_HAND || $attachment->ControllerId != $playerId) 
        {
            throw new \BgaUserException(self::_("Attachment is not in Player's Hand."));
        }

        $this->globals->set(GAME::CHOSEN_CARD, $id);

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        [$discount, $explanations] = $this->theah->getEquipDiscount($performer, $attachment);
        if ($discount != 0)
            $this->notify->player($performer->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                "explanations" => $explanations,
            ]);
        $this->globals->set(Game::DISCOUNT, $discount);
        $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

        $this->gamestate->nextState("attachmentSelected");
    }

    public function actHighDramaEquipActionAttachmentFromPlaySelected(int $id)
    {
        $this->theah->buildCity();
        $attachmentId = $id;

        $attachment = $this->theah->getCardById($attachmentId);
        if ($attachment == null) {
            throw new \BgaUserException(self::_("Attachment not found."));
        }

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        if ($attachment->Location != $performer->Location) {
            throw new \BgaUserException(self::_("Attachment is not at Performer's Location."));
        }

        $this->globals->set(GAME::CHOSEN_CARD, $attachmentId);

        [$discount, $explanations] = $this->theah->getEquipDiscount($performer, $attachment);
        if ($discount != 0)
            $this->notify->player($performer->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                "explanations" => $explanations,
            ]);
        $this->globals->set(Game::DISCOUNT, $discount);
        $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

        $this->gamestate->nextState("attachmentSelected");
    }

    public function actHighDramaEquipAttachment(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $attachmentId = $this->globals->get(GAME::CHOSEN_CARD);        
        $attachment = $this->getCardObjectFromDb($attachmentId);
        $equipType = $this->globals->get(Game::EQUIP_TYPE);

        //Sanity checks
        if ($attachment->Location == Game::LOCATION_HAND) 
        {
            $handCards = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
            $handCardIds = array_map(function($handCard) { return $handCard['id']; }, $handCards);
            if (!in_array($attachmentId, $handCardIds)) {
                throw new \BgaUserException(self::_("Attachment is not in Player's Hand."));
            }
        }

        // Let's Haggle can equip attachments only from the Bazaar
        if ($equipType == Game::LETS_HAGGLE_EQUIP_TYPE) 
        {
            if ($attachment->Location != Game::LOCATION_CITY_BAZAAR)
            {
                throw new \BgaUserException(self::_("Let's Haggle: Attachment is not at Bazaar."));
            }
        } 

        if ($attachment->Location != Game::LOCATION_HAND)
        {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($performer->Location);
            $attachmentIds = array_map(function($attachment) { return $attachment->Id; }, $attachmentsAtLocation);

            if (!in_array($attachmentId, $attachmentIds)) {
                throw new \BgaUserException(self::_("Attachment is not at Performer's Location."));
            }
        }
        if ($attachment->hasTrait("Armor") && $this->characterHasAttachmentOfType($performer, "Armor") && $attachment->hasEquipRestriction("Armor")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Armor attachment."));
        }
        if ($attachment->hasTrait("Attire") && $this->characterHasAttachmentOfType($performer, "Attire") && $attachment->hasEquipRestriction("Attire")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Attire attachment."));
        }
        if ($attachment->hasTrait("Weapon") && $this->characterHasAttachmentOfType($performer, "Weapon") && $attachment->hasEquipRestriction("Weapon")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Weapon attachment."));
        }

        $discount = $this->globals->get(Game::DISCOUNT);
        $explanations = $this->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

                //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $playerId = $this->getActivePlayerId();

        // If the Equip event was caused by Smuggled Item, we need to unequip it and discard it
        if ($equipType == Game::SMUGGLED_ITEM_EQUIP_TYPE)
        {
            $smuggledItemId = $this->globals->get(Game::SMUGGLED_ITEM_ATTACHMENT_ID);
            $smuggledItem = $this->theah->getCardById($smuggledItemId);

            $this->notifyAllPlayers("message", clienttranslate('${player_name} performed the Action from ${card_inject_code}.'), [
                "player_name" => $this->getPlayerNameById($playerId),
                "card_inject_code" => $smuggledItem->getInjectCode(),
            ]);

            $smuggledUnattachedEvent = EventFactory::createAttachmentUnequippedEvent($playerId, $performer->Id, $smuggledItem->Id);
            $this->theah->eventCheck($smuggledUnattachedEvent);
            $this->theah->queueEvent($smuggledUnattachedEvent);

            $smuggledDiscardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($smuggledItem->ControllerId, $smuggledItem->Id, $smuggledItem->Location, $smuggledItem->Id, $asEffect = false);
            $this->theah->queueEvent($smuggledDiscardEvent);
        }

        //If the Equip event was caused by Let's Haggle, we need to announce it here
        if ($equipType == Game::LETS_HAGGLE_EQUIP_TYPE)
        {
            $actionId = $this->globals->get(GAME::CHOSEN_ACTION);
            $action = $this->theah->getInPlayActionById($actionId);
            $scheme = $action->getOwningCard($this->theah);
            $action->SetUsed($this->theah, true);
            $this->updateCardObjectInDb($scheme);

            $this->notifyAllPlayers("message", clienttranslate('${player_name} performed the Action from ${scheme_inject_code}.'), [
                "player_name" => $this->getPlayerNameById($playerId),
                "scheme_inject_code" => $scheme->getInjectCode(),
            ]);
        }

        //Some attachments actually attach to different targets
        $actualTargetId = $attachment->getRequiredAttachTargetId($this->theah, $performer->Id);

        //Equip the attachment
        $equipAttachmentEvent = EventFactory::createAttachmentEquippedEvent($playerId, $actualTargetId, $attachmentId, $discount, $cost, $asAction = true, $explanations);
        $this->theah->eventCheck($equipAttachmentEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
            $this->theah->queueEvent($event);
        }

        $this->cards->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
        $this->theah->queueEvent($equipAttachmentEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $this->theah->queueEvent($actionResolvedEvent);

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("attachmentEquipped");
    }

    public function actHighDramaClaimActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanBasicClaim($player_id) == false) {
            throw new \BgaUserException(self::_("Claim Action is not allowed right now."));
        }

        $this->gamestate->nextState("claimActionStart");
    }

    public function actHighDramaClaimActionPerformerChosen(int $id)
    {
        $activePlayerId = $this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->theah->getCharacterById($id);
        if ($performer->Engaged) {
            throw new \BgaUserException(self::_("Performer cannot Claim because it is engaged."));
        }

        if ($performer->DashedInfluence) {
            throw new \BgaUserException(self::_("Performer cannot Claim because it has a Dashed Influence."));
        }

        $charactersInCity = $this->theah->getCharactersInCityByPlayerId($activePlayerId);
        $characterIds = array_map(fn($character) => $character->Id, $charactersInCity);

        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Performer is not in the City."));
        }

        $this->globals->set(Game::PRESSURING_PLAYER, $activePlayerId);
        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $this->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
        $this->globals->set(Game::IS_BASIC_CLAIM_ACTION, true);
        $pressureStats = $this->theah->getPressureStats($performer, Game::STAT_INFLUENCE);
        $claimEvent = EventFactory::createPressureOccuringEvent($activePlayerId, $performer->Id, $performer->Location, $pressureStats);
        $this->theah->eventCheck($claimEvent);
        $this->theah->queueEvent($claimEvent);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaChooseInPlayActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        if ($this->theah->playerHasInPlayActions($player_id) == false) {
            throw new \BgaUserException(self::_("In-Play Action is not allowed right now."));
        }

        $this->gamestate->nextState("inPlayActionStart");
    }

    public function actHighDramaInPlayActionChosen(string $actionId)
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $action = $this->theah->getInPlayActionById($actionId);

        if ($action == null) {
            throw new \BgaUserException(self::_("Action not found."));
        }

        if ( ! $action->isAvailabletoPlayer($player_id, $this->theah)) {
            throw new \BgaUserException(self::_("Action is not available to player."));
        }

        $this->globals->set(GAME::CHOSEN_ACTION, $action->Id);
        $this->globals->set(GAME::TRANSITION_INTERNAL_ID, $action->Id);

        // If a character action, the default performer is the owner of the action.
        // This can of course be overrident by the specific card
        if ($action instanceof CharacterAction)
            $this->globals->set(GAME::CHOSEN_PERFORMER, $action->OwnerId);

        if ($action->RequiresPerformerSelected)
        {
            $this->gamestate->nextState("requiresPerformerSelected");
        }
        else
        {
            $id = Game::THEAH_ID;
            if ($action instanceof CardAction)
                $id = $action->OwnerId;

            $event = EventFactory::createActionTriggeredEvent($player_id, $id, $actionId);
            $this->theah->eventCheck($event);
            $this->theah->queueEvent($event);
    
            $this->gamestate->nextState("inPlayActionChosen");
        }
    }

    public function actHighDramaInPlayActionPerformerChosen(int $id)
    {
        $playerId = (int)$this->getActivePlayerId();
        $performer = $this->getCardObjectFromDb($id);

        $actionId = $this->globals->get(GAME::CHOSEN_ACTION, '');

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $event = EventFactory::createActionTriggeredEvent($playerId, $performer->Id, $actionId);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("inPlayActionPerformerChosen");
    }

    public function actHighDramaChooseInHandActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        if ($this->theah->playerHasInHandActions($player_id) == false) {
            throw new \BgaUserException(self::_("In-Hand Action is not allowed right now."));
        }

        $this->gamestate->nextState("inHandActionStart");
    }

    public function actHighDramaInHandActionChosen(string $actionId)
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $action = $this->theah->getInHandActionById($actionId);
        if ($action == null) {
            throw new \BgaUserException(self::_("Action not found."));
        }

        if ( ! $action->isAvailabletoPlayer($player_id, $this->theah)) {
            throw new \BgaUserException(self::_("Action is not available to player."));
        }

        $this->globals->set(GAME::CHOSEN_ACTION, $action->Id);
        $this->globals->set(GAME::TRANSITION_INTERNAL_ID, $action->Id);

        if ($action->RequiresPerformerSelected)
        {
            $this->gamestate->nextState("requiresPerformerSelected");
        }
        else
        {
            [$discount, $explanations] = $this->theah->getActionFromHandDiscount($performer = null, $action);
            $this->globals->set(Game::DISCOUNT, $discount);
            $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

            if ($discount != 0)
                $this->notify->player($player_id, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
    
            $this->gamestate->nextState("inHandActionChosen");
        }
    }

    public function actHighDramaInHandActionPerformerChosen(int $id)
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->getCardObjectFromDb($id);

        $actionId = $this->globals->get(GAME::CHOSEN_ACTION, '');
        $action = $this->theah->getInHandActionById($actionId);

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        [$discount, $explanations] = $this->theah->getActionFromHandDiscount($performer, $action);
        $this->globals->set(Game::DISCOUNT, $discount);
        $this->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

        if ($discount != 0)
            $this->notify->player($playerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                "explanations" => $explanations,
            ]);

        $this->gamestate->nextState("inHandActionPerformerChosen");
    }

    public function actPayForInHandAction(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        $actionId = $this->globals->get(GAME::CHOSEN_ACTION, '');
        $action = $this->theah->getInHandActionById($actionId);

        if ($action == null) {
            throw new \BgaUserException(self::_("In-Hand Action not found."));
        }
        $risk = $this->theah->getCardById($action->OwnerId);

        //Sanity checks
        if ($risk->Location != Game::LOCATION_HAND || $risk->ControllerId != $playerId) {
            throw new \BgaUserException(self::_("Risk is not in Player's Hand."));
        }

        $performerId = 0;
        if ($action->RequiresPerformerSelected)
        {
            $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        }

        $discount = $this->globals->get(Game::DISCOUNT, 0);
        $explanations = $this->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
        $cost = $risk->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //Edge case: Bravos cannot be paid for with a Thug card
            if ($risk instanceof _01024 && $card->hasTrait('Thug'))
            {
                throw new \BgaUserException(self::_("A Thug cannot be used to pay for Bravos."));
            }

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
            $this->theah->queueEvent($event);
        }

        $this->cards->moveCard($risk->Id, Game::LOCATION_PURGATORY);
        $risk->Location = Game::LOCATION_PURGATORY;
        $this->updateCardObjectInDb($risk);

        $message = clienttranslate('${player_name} is performing the In-Hand Action [${action_name}] from ${card_inject_code}. ');
        if ($discount != 0)
        {
            $message .= clienttranslate('This was played at a cost of ${cost} Wealth (discount of ${discount}).');
            if ($explanations != '')
            {
                $message .= clienttranslate('<br>${explanations}');
            }
        }
        $this->notify->all("message", $message, [
            "i18n" => ["action_name"],
            "player_name" => $this->getActivePlayerName(),
            "action_name" => $action->Name,
            "card_inject_code" => $risk->getInjectCode(),
            "cost" => $cost,
            "discount" => $discount,
            "explanations" => $explanations,
        ]);

        $event = EventFactory::createRiskPlayedEvent($playerId, $risk->Id);
        $this->theah->queueEvent($event);

        $event = EventFactory::createActionTriggeredEvent($playerId, $performerId, $actionId);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $event = EventFactory::createCardDiscardedFromHandEvent($risk->OwnerId, $risk->Id, $sourceId = 0, $asPayment = false, $asPlayed = true);
        $this->theah->queueEvent($event);

        //Reset the player pass count 
        $this->globals->set(GAME::PASS_COUNT, 0);
        
        $this->gamestate->nextState("actionPaidFor");
    }

    public function actHighDramaChooseBruteStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();
        if ($this->theah->playerHasBrutes($player_id) == false) {
            throw new \BgaUserException(self::_("Brute is not allowed right now."));
        }

        $this->gamestate->nextState("bruteStart");
    }

    public function actHighDramaBruteActionBruteChosen(int $id)
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $brute = $this->getCardObjectFromDb($id);
        if ($brute == null || $brute->Location != Game::LOCATION_HAND || $brute->ControllerId != $playerId) 
        {
            throw new \BgaUserException(self::_("Brute is not in Player's Hand."));
        }

        $discount = $this->theah->getPlayBruteDiscount($brute);
        $this->globals->set(Game::DISCOUNT, $discount);

        $this->globals->set(GAME::CHOSEN_CARD, $id);

        $this->gamestate->nextState("bruteChosen");
    }

    public function actPayForBrute(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        $bruteId = $this->globals->get(GAME::CHOSEN_CARD);
        $brute = $this->getCardObjectFromDb($bruteId);

        //Sanity checks
        if ($brute == null || $brute->Location != Game::LOCATION_HAND || $brute->ControllerId != $playerId) 
        {
            throw new \BgaUserException(self::_("Brute is not in Player's Hand."));
        }

        $discount = $this->globals->get(Game::DISCOUNT);
        $cost = $brute->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Brute is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $playerId = $this->getActivePlayerId();

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) 
        {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
            $this->theah->queueEvent($event);
        }

        $musterEvent = EventFactory::createCharacterMusteredEvent($playerId, $brute->Id, Game::LOCATION_PLAYER_HOME);
        $this->theah->queueEvent($musterEvent);

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $this->theah->queueEvent($actionResolvedEvent);

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("brutePaidFor");
    }

    public function actHighDramaChallengeActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanBasicChallenge($player_id) == false) {
            throw new \BgaUserException(self::_("Challenge Action is not allowed right now."));
        }

        //Set the challenge to the default stat
        $this->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
        $this->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);

        $this->gamestate->nextState("challengeActionStart");
    }

    public function actHighDramaChallengeActionPerformerChosen(int $id)
    {
        $activePlayerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->theah->getCharacterById($id);

        //Special case for Carmella Vanessa Slavaggi
        if ($performer instanceof _01178)
        {
            if (! $performer->canChallenge())
            {
                throw new \BgaUserException(self::_("Performer cannot Challenge."));
            }
        }
        else
        {
            if (! $performer->canChallenge() || $performer->Engaged)
            {
                throw new \BgaUserException(self::_("Performer cannot Challenge."));
            }
        }

        $characters = $this->theah->getCharactersInCityByPlayerId($activePlayerId);

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $characters);

        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Performer is not in the City."));
        }

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        $charactersAtLocation = array_filter($charactersAtLocation, fn($character) => $character->ControllerId && $character->ControllerId != $activePlayerId );
        if (count($charactersAtLocation) == 0)
        {
            throw new \BgaUserException(self::_("No Challengable Characters at Performer's location."));
        }

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaChallengeActionTargetChosen(int $id)
    {
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($id);

        if ($target->Location != $performer->Location) {
            throw new \BgaUserException(self::_("Target is not in the same location as your Peformer."));
        }

        $this->globals->set(GAME::CHOSEN_TARGET, $target->Id);

        $this->gamestate->nextState("targetChosen");
    }

    public function actHighDramaChallengeActionTechniqueActivated(string $techniqueId)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        $technique = $this->theah->getTechniqueById($techniqueId);
        if ($technique == null) {
            throw new \BgaUserException(self::_("Technique not found."));
        }

        $this->globals->set(GAME::CHOSEN_TECHNIQUE, $technique->Id);
        $this->globals->set(GAME::TRANSITION_INTERNAL_ID, $technique->Id);

        $owner = $technique->getOwningCard($this->theah);
        $event = EventFactory::createTechniqueActivatedEvent($playerId, $owner->Id, $technique->Id);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->stIssueChallenge();

        $this->gamestate->nextState("techniqueActivated");
    }

    public function actHighDramaChallengeActionActivateTechnique_Pass()
    {
        $this->stIssueChallenge();

        $this->gamestate->nextState("pass");
    }

    public function actHighDramaChallengeActionAccept()
    {
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        $event = EventFactory::createChallengeAcceptedEvent($performer->Id, $target->Id);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} ACCEPTS The Challenge.'), [
            "player_name" => $this->getActivePlayerName(),
        ]);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, true);

        $this->gamestate->nextState("");
    }

    public function actHighDramaChallengeActionReject()
    {
        $challengeType = $this->globals->get(Game::CHALLENGE_TYPE);
        if ($challengeType == Game::EPEE_SANGLANTE_CHALLENGE_TYPE)
        {
            throw new \BgaUserException(self::_("Épée Sanglante: Refusing a Challenge is not allowed."));
        }

        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        $event = EventFactory::createChallengeRejectedEvent($performer->Id, $target->Id);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, false);

        $this->gamestate->nextState("");
    }

    public function actHighDramaChallengeActionIntervene(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $this->theah->buildCity();
        $character = $this->theah->getCardById($id);

        $target = $this->theah->getCardById($this->globals->get(GAME::CHOSEN_TARGET));
        if ($target->Location != $character->Location) {
            throw new \BgaUserException(self::_("Character is not at the same location"));
        }    

        //Special case for Carmella Vanessa Slavaggi
        if ($character instanceof _01178)
        {
            if (! $character->canIntervene())
            {
                throw new \BgaUserException(self::_("Character cannot Intervene."));
            }
        }
        else
        {
            if (! $character->canIntervene() || $character->Engaged)
            {
                throw new \BgaUserException(self::_("Character cannot Intervene."));
            }
        }

        $challengeType = $this->globals->get(Game::CHALLENGE_TYPE);
        if ($challengeType == Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE && ! $character instanceof Leader) {
            throw new \BgaUserException(self::_("Legendary Reputation: Only Leaders can Intervene"));
        }


        //Reset the conditions for defender
        $target->removeCondition(Game::DUEL_DEFENDER);
        $character->addCondition(Game::DUEL_DEFENDER);
        $this->globals->set(Game::CHOSEN_TARGET, $character->Id);

        $interveneEvent = $this->theah->createEvent(Events::CharacterIntervened);
        if ($interveneEvent instanceof EventCharacterIntervened)
        {
            $interveneEvent->playerId = $playerId;
            $interveneEvent->oldTargetId = $target->Id;
            $interveneEvent->newTargetId = $character->Id;
        }    
        $this->theah->eventCheck($interveneEvent);

        $engageRequired = true;
        //If Odette was the target, and intervening character is a Musketeer, they are not required to engage
        if ($target instanceof _01062 && $character->hasTrait("Musketeer"))
        {
            $this->notifyAllPlayers("message", clienttranslate('${character_name} does not need to engage because they are a Musketeer intervening for Odette.'), [
                "i18n" => ["character_name"],
                "character_name" => $character->Name,
            ]);

            $engageRequired = false;
        }

        if ($engageRequired)
        {
            // Intervening character is now engaged
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $character->Id);
            $this->theah->eventCheck($engageEvent);
            $this->theah->queueEvent($engageEvent);
        }
        
        $this->theah->queueEvent($interveneEvent);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, true);

        $this->gamestate->nextState("");
    }    

    public function actDuelActionChooseTechnique()
    {
        $this->theah->buildCity();

        $actor = $this->theah->getDuelRoundActor();

        $techniques = $this->theah->getAvailableCharacterTechniques($actor);
        if (count($techniques) == 0) {
            throw new \BgaUserException(sprintf(self::_("No Techniques available for %s."), $actor->Name));
        }

        $this->gamestate->nextState("chooseTechnique");
    }

    public function actDuelTechniqueChosen(string $techniqueId)
    {
        $playerId = $this->getActivePlayerId();
        $this->theah->buildCity();

        $actor = $this->theah->getDuelRoundActor();

        $technique = $this->theah->getTechniqueById($techniqueId);
        if ($technique == null) {
            throw new \BgaUserException(self::_("Technique not found."));
        }
        
        if ( ! $this->theah->isTechniqueOwnedByCharacter($technique, $actor)) {
            throw new \BgaUserException(self::_("Technique does not belong to the Actor."));
        }

        $this->globals->set(GAME::CHOSEN_TECHNIQUE, $technique->Id);
        $this->globals->set(GAME::CHOSEN_TECHNIQUE_IS_MAIN, true);
        $this->globals->set(GAME::TRANSITION_INTERNAL_ID, $technique->Id);

        $adversaryId = $this->theah->getDuelOpponentId($actor->Id);

        $activateEvent = EventFactory::createTechniqueActivatedEvent($playerId, $actor->Id, $technique->Id);
        $this->theah->eventCheck($activateEvent);
        $this->theah->queueEvent($activateEvent);

        $resolveEvent = EventFactory::createResolveTechniqueEvent($playerId, $actor->Id, $adversaryId, $technique->Id);
        $this->theah->eventCheck($resolveEvent);
        $this->theah->queueEvent($resolveEvent);

        $threatEvent = EventFactory::createDuelCalculateTechniqueValuesEvent($actor->Id, $adversaryId, $technique->Id);
        $this->theah->eventCheck($threatEvent);
        $this->theah->queueEvent($threatEvent);

        $this->gamestate->nextState("techniqueChosen");
    }

    public function actDuelActionGamble()
    {
        $actor = $this->theah->getDuelRoundActor();
        [$cardCount, $explanations] = $this->theah->getNumberOfGambleCardsToReveal($actor);
        if ($explanations != '')
            $this->notify->player($actor->ControllerId, "message", clienttranslate('Private: Explanations for modification of number of gamble cards to reveal:<br>${explanations}'), [
                "explanations" => $explanations,
            ]);

        $this->globals->set(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_NORMAL);
        $this->globals->set(Game::GAMBLE_REVEAL_COUNT, $cardCount);
        $this->globals->set(Game::GAMBLE_REVEAL_EXPLANATIONS, $explanations);

        $this->gamestate->nextState("chooseGambleCard");
    }

    public function actDuelActionChooseCombatCard(string $cardId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null) {
            throw new \BgaUserException(self::_("Card not found."));
        }

        $playerId = $this->getActivePlayerId();
        if ($card->OwnerId != $playerId) {
            throw new \BgaUserException(self::_("Card does not belong to the player."));
        }

        if ($card->Location != Game::LOCATION_HAND) {
            throw new \BgaUserException(self::_("Card is not in your hand."));
        }

        $this->globals->set(Game::CHOSEN_CARD, $card->Id);

        if ($card->hasManeuversAvailableToPlayer($playerId, $this->theah))
        {
            $this->gamestate->nextState("useManeuver");
        }
        else
        {
            $card->Location = Game::LOCATION_DUELING_LINE;
            $this->updateCardObjectInDb($card);
            $this->cards->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $playerId);

            $this->gamestate->nextState("applyCombatCardStats");
        }   
    }

    public function actDuelUseManeuverFromCombatCard(string $maneuverId)
    {
        $this->theah->buildCity();

        $maneuver = $this->theah->getManeuverById($maneuverId);
        if ($maneuver == null) {
            throw new \BgaUserException(self::_("Maneuver not found."));
        }
        
        $cardId = $this->globals->get(Game::CHOSEN_CARD);
        $card = $this->theah->getCardById($cardId);

        if ($maneuver->OwnerId != $card->Id) {
            throw new \BgaUserException(self::_("Maneuver does not belong to chosen combat card."));
        }

        $this->globals->set(Game::CHOSEN_MANEUVER, $maneuverId);

        $this->gamestate->nextState("maneuverChosen");
    }

    public function actDuelUseManeuverFromCombatCardDeclined()
    {
        $playerId = $this->getActivePlayerId();
        $cardId = $this->globals->get(Game::CHOSEN_CARD);
        $card = $this->theah->game->getCardObjectFromDb($cardId);

        //Remove card from hand
        $card->Location = Game::LOCATION_DUELING_LINE;
        $this->updateCardObjectInDb($card);
        $this->cards->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $playerId);

        $this->gamestate->nextState("maneuverDeclined");
    }

    public function actDuelPayForManeuverFromCombatCard(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        
        $actor = $this->theah->getDuelRoundActor();

        $maneuverId = $this->globals->get(Game::CHOSEN_MANEUVER);
        $maneuver = $this->theah->getManeuverById($maneuverId);
        
        $cardId = $this->globals->get(Game::CHOSEN_CARD);
        $card = $this->theah->getCardById($cardId);

        $discount = $this->globals->get(Game::DISCOUNT);        

        $cost = $card->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);

            if ($payCard == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $payCard->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $this->notifyAllPlayers("message", clienttranslate('${player_name} is playing ${card_inject_code} as their Combat Card.'), [
            "i18n" => ["player_name", "effect_name", "maneuver_name"],
            "player_name" => $this->getActivePlayerName(),
            "card_inject_code" => $card->getInjectCode(),
        ]);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($payCard->OwnerId, $payCard->Id, $sourceId = 0, $asPayment = true);
            $this->theah->queueEvent($event);
        }

        $activateEvent = EventFactory::createManeuverActivatedEvent($playerId, $cardId, $maneuver->Id);
        $this->theah->eventCheck($activateEvent);
        $this->theah->queueEvent($activateEvent);

        $adversaryId = $this->theah->getDuelOpponentId($actor->Id);

        $resolveEvent = $this->theah->createEvent(Events::ResolveManeuver);
        if ($resolveEvent instanceof EventResolveManeuver)
        {
            $resolveEvent->playerId = $playerId;
            $resolveEvent->adversaryId = $adversaryId;
            $resolveEvent->maneuverId = $maneuver->Id;
        }
        $this->theah->eventCheck($resolveEvent);
        $this->theah->queueEvent($resolveEvent);

        $threatEvent = $this->theah->createEvent(Events::DuelCalculateManeuverValues);
        if ($threatEvent instanceof EventDuelCalculateManeuverValues)
        {
            $threatEvent->actorId = $actor->Id;
            $threatEvent->adversaryId = $adversaryId;
            $threatEvent->maneuverId = $maneuver->Id;
        }
        $this->theah->eventCheck($threatEvent);
        $this->theah->queueEvent($threatEvent);

        //Remove card from hand
        $card->Location = Game::LOCATION_DUELING_LINE;
        $this->updateCardObjectInDb($card);
        $this->cards->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $playerId);

        $this->gamestate->nextState("maneuverPaidFor");
    }

    public function actGambleCardChosen(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $deckName = $this->getPlayerFactionDeckName($playerId);

        $deckCard = $this->cards->getCard($id);
        if ($deckCard == null) {
            throw new \BgaUserException(self::_("Card not found."));
        }

        $card = $this->getCardObjectFromDb($id);
        if ($card->Location != $deckName) {
            throw new \BgaUserException(self::_("Card is not in your faction deck."));
        }

        $count = $this->globals->get(Game::GAMBLE_REVEAL_COUNT, 2);
        $cards = $this->getCardsOnTopOfPlayerFactionDeck($playerId, $count);
        if (!in_array($id, array_column($cards, 'id'))) {
            throw new \BgaUserException(self::_("Chosen card is not in the top $count cards of your faction deck."));
        }

        //Sink the cards that are not chosen
        $cards = array_filter($cards, fn($card) => $card['id'] != $id);
        foreach ($cards as $notChosenCard) 
        {
            $this->cards->insertCardOnExtremePosition($notChosenCard['id'], $deckName, false);
        }

        $this->globals->set(Game::CHOSEN_CARD, $id);

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);

        $this->globals->set(Game::DUEL_GAMBLED, true);
        $gambleType = $this->globals->get(Game::GAMBLE_TYPE, Game::GAMBLE_TYPE_NORMAL);
        if ($gambleType == Game::GAMBLE_TYPE_NORMAL)
        {
            //Set that the player has gambled
            $sql = "UPDATE duel_round set gambled = 1 WHERE duel_id = $duelId AND round = $round";
            $this->DbQuery($sql);
        }

        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $adversaryId = $this->theah->getDuelOpponentId($actorId);

        $explanations = $this->globals->get(Game::GAMBLE_REVEAL_EXPLANATIONS, '');
        $event = $this->theah->createEvent(Events::DuelPlayerGambled);
        if ($event instanceof EventDuelPlayerGambled)
        {
            $event->playerId = $playerId;
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->chosenCardId = $id;
            $event->revealCount = $count;
            $event->explanations = $explanations;
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);        

        $card->Location = Game::LOCATION_DUELING_LINE;
        $this->updateCardObjectInDb($card);
        $this->cards->moveCard($card->Id, Game::LOCATION_DUELING_LINE, $playerId);

        if ($card->hasManeuversAvailableToPlayer($playerId, $this->theah))
            $this->gamestate->nextState("useManeuver");
        else
            $this->gamestate->nextState("noManeuver");
    }
    

    public function actDuelDoneRound()
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);    
        $type = $this->globals->get(Game::DUEL_TYPE);

        $actor = $this->theah->getDuelRoundActor();

        if ($round == 1 && $type != Game::VLADISLAV_DUEL_TYPE)
        {
            //How many times has the player gambled this duel?
            $sql = "SELECT count(gambled) FROM duel_round where duel_id = $duelId and player_id = {$actor->ControllerId}";
            $gamblesCount = $this->getUniqueValueFromDB($sql);

            $gamblesLeft = $actor->ModifiedFinesse - $gamblesCount;
            $handCards = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $actor->ControllerId);
            $handCardsCount = count($handCards);

            $sql = "SELECT count(*) FROM duel_round_combat_card where duel_id = $duelId AND round = $round";
            $combatCardsCount = $this->getUniqueValueFromDB($sql);

            //Check to see if a combat card was played (but if you can't gamble or have no cards in hand, you can pass)
            if ($combatCardsCount == 0 && ($handCardsCount > 0 || $gamblesLeft > 0))
            {
                throw new \BgaUserException(self::_("For the first round, you must either gamble or a combat card must be played."));
            }
        }
        
        $event = $this->theah->createEvent(Events::DuelActionsDone);
        if ($event instanceof EventDuelActionsDone)
        {
            $event->playerId = $this->getActivePlayerId();
            $event->actorId = $actor->Id;
            $event->adversaryId = $this->theah->getDuelOpponentId($actor->Id);
        }
        $this->theah->queueEvent($event);
        
        $this->gamestate->nextState("doneWithRound");
    }

    public function actDuelEndDuel()
    {
        $duelType = $this->globals->get(Game::DUEL_TYPE);
        if ($duelType != Game::VLADISLAV_DUEL_TYPE)
        {
            throw new \BgaUserException(self::_("Duel is not type Vladislav duel."));
        }

        $this->gamestate->nextState("doneWithRound");
    }

    public function actDuskPhaseCardsDiscarded(string $ids)
    {
        $playerId = $this->getCurrentPlayerId();
        $sql = "SELECT leader_card_id as leaderId FROM player WHERE player_id = $playerId";
        $leaderId = $this->getUniqueValueFromDB($sql);
        $leader = $this->getCardObjectFromDb($leaderId);

        //Get the cards in hand
        $cards = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        $handSize = count($cards);

        $expectedDiscard = $handSize - $leader->Panache;

        $cardIds = json_decode($ids, true);
        if ($expectedDiscard != count($cardIds))
            throw new \BgaUserException(sprintf(self::_("You must discard exactly %d cards."), $expectedDiscard));
        
        foreach ($cardIds as $cardId) 
        {
            $this->cards->moveCard($cardId, Game::LOCATION_PURGATORY);
        }
        
        $this->gamestate->setPlayerNonMultiactive($playerId, 'cardsDiscarded'); // deactivate player; if none left, transition to 'dayPlanned' state
    }

    public function actFromCardPass()
    {
        $this->theah->buildCity();
        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardPass($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId);
    }

    public function actFromCardWithId(int $id)
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithId($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $id);
    }

    public function actFromCardWithIds(string $ids)
    {
        $this->theah->buildCity();
        $ids = json_decode($ids, true);

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithIds($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $ids);
    }

    public function actFromCardWithLocations(string $locations)
    {
        $this->theah->buildCity();
        $locations = json_decode($locations, true);

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithIds($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $locations);
    }

    public function actFromCardWithActionId(int $actionSourceId, string $actionId)
    {
        $this->theah->buildCity();
        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithActionId($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $internalId, $actionSourceId, $actionId);
    }

    public function actReactionForState(string $reactionId)
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID);
        $state = $this->gamestate->state_id();

        if ($sourceId == Game::THEAH_ID)
        {
            $reaction = $this->theah->getTheahReactionById($internalId);
            $reaction->performReaction($this, $state, $internalId, $reactionId);    
        }
        else
        {
            $card = $this->theah->getCardById($sourceId);
            $card->reactionFromCard($this, $state, $internalId, $reactionId);
        }

        $this->globals->set(Game::REACTION_ID, $reactionId);
    }

    public function actPayForReaction(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        
        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $card = $this->theah->getCardById($sourceId);

        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID);
        $reaction = $card->getReactionById($internalId);

        $reactionId = $this->globals->get(Game::REACTION_ID);

        $discount = $this->theah->getReactionFromHandDiscount($reaction);

        $cost = $card->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);

            if ($payCard == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += $payCard->hasTrait("Wealth") ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($payCard->OwnerId, $payCard->Id, $sourceId = 0, $asPayment = true);
            $this->theah->queueEvent($event);
        }

        $announcement = $reaction->getReactionAnnouncement($this, $this->gamestate->state_id(), $internalId, $reactionId);
        if ($announcement != "")
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} ${announcement}'), [
                "player_name" => $this->getActivePlayerName(),
                "announcement" => $announcement,
            ]);
        }

        $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = false, $asPlayed = true);
        $this->theah->queueEvent($event);

        if ($reaction instanceof CancelReaction)
        {
            $riskReactionTriggered = EventFactory::createRiskReactionTriggeredEvent($playerId,  $card->Id, $internalId, $reactionId);
            $riskReactionTriggered->priority = Event::HIGHEST_PRIORITY;
            $this->theah->stackEvent($riskReactionTriggered);
            
            $riskPlayed = EventFactory::createRiskPlayedEvent($playerId, $card->Id);   
            $riskPlayed->priority = Event::HIGHEST_PRIORITY;
            $this->theah->stackEvent($riskPlayed);
        }
        else
        {

            $riskPlayed = EventFactory::createRiskPlayedEvent($playerId, $card->Id);    
            $this->theah->queueEvent($riskPlayed);
    
            $riskReactionTriggered = EventFactory::createRiskReactionTriggeredEvent($playerId,  $card->Id, $internalId, $reactionId);
            $this->theah->queueEvent($riskReactionTriggered);
        }

        $reaction->reactionPaidFor($this, $this->gamestate->state_id(), $internalId, $reactionId);

        $this->gamestate->nextState("paid");
   }

}