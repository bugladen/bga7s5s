<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventClaimOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelPlayerGambled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

trait FrameworkActionsTrait
{
    public function actPass(string $transition = ""): void
    {
        // Retrieve the active player ID.
        $playerId = $this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("message", clienttranslate('${player_name} passes.'), [
            "player_id" => $playerId,
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
    
    public function actPickDeck(string $deck_type, string $deck_id): void
    {
        $playerId = $this->getCurrentPlayerId();

        $sql = "UPDATE player SET deck_source = '$deck_type', deck_id = '$deck_id'  WHERE player_id='$playerId'";
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
        $this->cards->moveCard($scheme, Game::LOCATION_PURGATORY);
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

    public function actPlanningPhase_01016_2(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $card = $this->getCardObjectFromDb($id);

        $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($playerId, $card->Id);
        $this->theah->eventCheck($removeEvent);

        $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
        $this->theah->eventCheck($addEvent);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->globals->set(GAME::CHOSEN_CARD, $card->Id);

        $this->gamestate->nextState("cardChosen");
    }

    public function actPlanningPhase_01044(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $card = $this->getCardObjectFromDb($id);

        $removeEvent = $this->theah->createEvent(Events::CardRemovedFromPlayerDiscardPile);
        if ($removeEvent instanceof EventCardRemovedFromPlayerDiscardPile) {
            $removeEvent->card = $card;
            $removeEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($removeEvent);

        $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
        $this->theah->eventCheck($addEvent);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01045(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();
        $card = $this->getCardObjectFromDb($id);

        $removeEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($playerId, $card->Id);
        $this->theah->eventCheck($removeEvent);

        $addEvent = EventFactory::createCardAddedToCityDeckEvent($playerId, $card->Id, true);
        $this->theah->eventCheck($addEvent);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->gamestate->nextState();
    }

    public function actPlanningPhase_01125(string $locations)
    {
        $location = json_decode($locations, true)[0];
        
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Adding Reknown to Location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to place reknown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);

        $this->gamestate->nextState("reknownPlaced");
    }

    public function actPlanningPhase_01125_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to pass placing reknown onto a location.  Per The Boar\'s Guile you will now choose a city location to move a Reknown FROM.'), []);

        $this->gamestate->nextState("pass");
    }

    public function actPlanningPhase_01125_2(string $locations)
    {
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $reknown = $this->getReknownForLocation($location);
        if ($reknown <= 0) 
            throw new \BgaUserException(sprintf(self::_("%s does not have any reknown to move."), $location));
        
        $event = EventFactory::createReknownRemovedFromLocationEvent($this->getActivePlayerId(), $location, 1, "The Boar's Guile: Moving Reknown from one Location to an adjacent location");
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to move reknown from ${location}.  You must now choose a location to move the Reknown TO.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);
        
        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        $this->gamestate->nextState("locationChosen");
    }

    public function actPlanningPhase_01125_2_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have passed choosing a location to move reknown from.  Per The Boar\'s Guile you must now choose an enemy character to target.'), []);

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
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have chosen to move reknown to ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            'i18n' => ['location'],
            "location" => $location
        ]);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_4(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $playerName = $this->getActivePlayerName();
        $character = $this->getCardObjectFromDb($id);

        $this->notifyAllPlayers('yevgeniAdversaryChosen', 
            clienttranslate('${player_name} has chosen <strong>${character}</strong> as Yevgeni\'s Adversary.'), [
            'i18n' => ['character'],
            "player_name" => $playerName,
            "character" => $character->Name,
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
                $event->source = "Leshiye of the Wood: Adding Reknown to Location";
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
            clienttranslate('${player_name} has chosen ${location} as the Chosen Location for <strong>${card_name}</strong>.'), [
            'i18n' => ['location', 'card_name'],
            "player_name" => $playerName,
            "location" => $leshiyeLocation,
            "card_name" => $scheme->Name,
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
                $event->source = "Leshiye of the Wood: Adding Reknown to Location";
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

    public function actPlanningPhase_01143(string $locations)
    {
        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = $playerName;
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        //Get all cards in the chosen location
        $this->theah->buildCity();
        $cards = $this->theah->getCardObjectsAtLocation($location);
        foreach ($cards as $card)
        {
            //Discard all city cards
            if ($card instanceof ICityDeckCard)
            {
                $discard = $this->theah->createEvent(Events::CardAddedToCityDiscardPile);
                if ($discard instanceof EventCardAddedToCityDiscardPile)
                {
                    $discard->cardId = $card->Id;
                    $discard->fromLocation = $location;
                    $discard->playerId = $playerId;
                }

                $this->theah->queueEvent($discard);
            }
        }

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
            $event->source = $playerName;
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
            $event->source = $playerName;
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

    public function actPlanningPhase_01150(string $locations)
    {
        $playerName = $this->getActivePlayerName();

        $locations = json_decode($locations, true);
        $location = array_shift($locations);

        $playerId = $this->getActivePlayerId();
        $removeEvent = EventFactory::createReknownRemovedFromLocationEvent($playerId, $location, 1, $playerName);
        $this->theah->eventCheck($removeEvent);

        $addEvent = EventFactory::createReknownAddedToLocationEvent($playerId, Game::LOCATION_CITY_FORUM, 1, $playerName);
        $this->theah->eventCheck($addEvent);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01152(string $locations)
    {
        $location = json_decode($locations, true)[0];
        
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = "Until Morale Improves: Adding Reknown to Location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("reknownPlaced");
    }

    public function actPlanningPhase_01152_2(string $locations)
    {
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $reknown = $this->getReknownForLocation($location);
        if ($reknown <= 0)
            throw new \BgaUserException(sprintf(self::_("%s does not have any reknown to move."), $location));

        $playerId = $this->getActivePlayerId();
        $event = EventFactory::createReknownRemovedFromLocationEvent($playerId, $location, 1, "Until Morale Improves: Moving Reknown from one Location to an adjacent location");
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        $this->gamestate->nextState("locationChosen");
    }

    public function actPlanningPhase_01152_3(string $locations)
    {
        $location = json_decode($locations, true)[0];

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = "Until Morale Improves: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

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

        $discount = $this->globals->get(Game::DISCOUNT);        

        $cost = $character->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);

            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Mercenary is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $playerId = $this->getActivePlayerId();

        //Recruit the character
        $recruitCharacterEvent = $this->theah->createEvent(Events::CharacterRecruited);
        if ($recruitCharacterEvent instanceof EventCharacterRecruited) {
            $recruitCharacterEvent->character = $character;
            $recruitCharacterEvent->playerId = $playerId;
            $recruitCharacterEvent->discount = $discount;
            $recruitCharacterEvent->cost = $cost;
        }
        $this->theah->eventCheck($recruitCharacterEvent);
        $this->theah->queueEvent($recruitCharacterEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
            //No check needed
            $this->theah->queueEvent($event);
        }
    }

    public function actHighDramaBeginning_01144(int $recruitId, string $payWithCards)
    {
        $this->actRecruitMercenary($recruitId, $payWithCards);
        $this->gamestate->nextState("");
    }

    public function actPlanningPhaseEnd_01098(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $leader = $this->getCardObjectFromDb($id);
        $chosenPlayerId = $leader->ControllerId;

        //Get the chosen player's name
        $chosenPlayerName = $this->getPlayerNameById($chosenPlayerId);

        //Get the chosen player's hand
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $chosenPlayerId);

        //Randomly select a card from the hand
        $card = $hand[array_rand($hand)];
        $pickedCard = $this->getCardObjectFromDb($card['id']);

        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        //Get the chosen scheme card for the active player and updated it with the chosen card
        $scheme = $this->getPlayerChosenScheme($playerId);
        if ($scheme instanceof _01098) {
            $scheme->EmbargoedCardId = $pickedCard->Id;
            $this->updateCardObjectInDb($scheme);
        }

        $this->globals->set(GAME::CATS_EMBARGO, $pickedCard->Id);

        $this->notifyAllPlayers('message', 
            clienttranslate('${player_name} reveals <strong>${picked_card}</strong> randomly from <strong>${chosen_player_name}</strong>\'s hand.'), [
            'i18n' => ['picked_card'],
            "player_name" => $playerName,
            "chosen_player_name" => $chosenPlayerName,
            "picked_card" => $pickedCard->Name,
            "card" => $pickedCard->getPropertyArray($this),
        ]);

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

    public function actHighDramaMoveActionPerformerChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];
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
        $this->cards->moveCard($cardId, $location, $card->ControllerId);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} performed a Move Action.'), [
        "player_name" => $playerName,
        ]);

        $movedHome = $this->theah->createEvent(Events::CardMoved);
        $movedHome = EventFactory::createCardMovedEvent($card->ControllerId, $card->Id, $card->Location, $location, $card->Location != Game::LOCATION_PLAYER_HOME);
        $this->theah->eventCheck($movedHome);
        $this->theah->queueEvent($movedHome);

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

        $this->gamestate->nextState("recruitActionStart");
    }

    public function actHighDramaRecruitActionPerformerChosen(string $ids)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $id = json_decode($ids, true)[0];
        $character = $this->theah->getCharacterById($id);

        if (!$this->theah->cardInCity($character)) {
            throw new \BgaUserException(self::_("Character is not in the City."));
        }

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  
        $charactersThatCanReruit = [];
        foreach ($characters as $character) {
            $charactersAtLocation = $this->theah->getCharactersAtLocation($character->Location);
            $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return $character->isMercenary(); });
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }
        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanReruit);
        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Character not in a state to recruit mercenaries."));
        }

        $this->globals->set(GAME::CHOSEN_CARD, $character->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaRecruitActionParleyYes()
    {
        $id = $this->globals->get(GAME::CHOSEN_CARD);
        $character = $this->getCardObjectFromDb($id);

        //Set the discount for recruiting a mercenary.
        $discount = $character->getParleyDiscount(true);
        $this->globals->set(Game::DISCOUNT, $discount);

        $this->gamestate->nextState("parleyChosen");
    }

    public function actHighDramaRecruitActionParleyNo()
    {
        $this->globals->set(Game::DISCOUNT, 0);
        $this->gamestate->nextState("parleyChosen");
    }

    public function actHighDramaRecruitActionMercenaryChosen(int $recruitId, string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();
        $discount = $this->globals->get(Game::DISCOUNT);
        $performerId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->theah->getCharacterById($performerId);

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return $character->isMercenary(); });        
        $mercenaryIds = array_map(function($character) { return $character->Id; }, $mercenariesAtLocation);
        if (!in_array($recruitId, $mercenaryIds)) {
            throw new \BgaUserException(self::_("Chosen character is not a Mercenary at the Performer's Location."));
        }        

        $this->notifyAllPlayers("message", clienttranslate('${player_name} chose <strong>${card_name}</strong> to perform a Recruit Action.'), [
            'i18n' => ['card_name'],
            "player_name" => $playerName,
            "card_name" => $performer->Name,
        ]);

        if ($discount > 0)
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} chose to Parley with <strong>${card_name}</strong>.'), [
                'i18n' => ['card_name'],
                "player_name" => $playerName,
                "card_name" => $performer->Name,
            ]);
            
            $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performer->Id);
            $this->theah->eventCheck($engageEvent);
            $this->theah->queueEvent($engageEvent);
        }

        $this->actRecruitMercenary($recruitId, $payWithCards);
        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("mercenaryChosen");
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

    public function actHighDramaEquipActionPerformerChosen(string $ids)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $id = json_decode($ids, true)[0];
        $performer = $this->theah->getCharacterById($id);
        $handHasAttachments = $this->handHasAttachments($playerId);

        $characters = $this->theah->getCharactersInPlayByPlayerId($playerId);        
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });
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

    public function actHighDramaEquipActionAttachmentFromHandSelected(int $attachmentId)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        //Get the chosen player's hand
        $handCard = $this->cards->getCard($attachmentId);
        $card = $this->getCardObjectFromDb($handCard['id']);
        if ($card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId) {
            throw new \BgaUserException(self::_("Attachment is not in Player's Hand."));
        }

        $attachment = $this->getCardObjectFromDb($attachmentId);
        $this->globals->set(GAME::CHOSEN_CARD, $attachmentId);

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

        $discount = $this->theah->getEquipDiscount($performer, $attachment);
        $this->globals->set(Game::DISCOUNT, $discount);

        $this->gamestate->nextState("attachmentSelected");
    }

    public function actHighDramaEquipActionAttachmentFromPlaySelected(string $ids)
    {
        $this->theah->buildCity();
        $attachmentId = json_decode($ids, true)[0];

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

        $discount = $this->theah->getEquipDiscount($performer, $attachment);
        $this->globals->set(Game::DISCOUNT, $discount);

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

        //Sanity checks
        if ($attachment->Location == Game::LOCATION_HAND)
        {
            //Get the chosen player's hand
            $handCard = $this->cards->getCard($attachmentId);
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId) {
                throw new \BgaUserException(self::_("Attachment is not in Player's Hand."));
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
        if (in_array("Armor", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Armor")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Armor attachment."));
        }
        if (in_array("Attire", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Attire")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Attire attachment."));
        }
        if (in_array("Weapon", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Weapon")) {
            throw new \BgaUserException(self::_("Character cannot have more than one Weapon attachment."));
        }

        $discount = $this->globals->get(Game::DISCOUNT);
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
            $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        $playerId = $this->getActivePlayerId();

        //Some attachments actually attach to different targets
        $actualTargetId = $attachment->getRequiredAttachTargetId($this->theah, $performer->Id);

        //Equip the attachment
        $equipAttachmentEvent = EventFactory::createAttachmentEquippedEvent($playerId, $actualTargetId, $attachmentId, $discount, $cost);
        $this->theah->eventCheck($equipAttachmentEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
            $this->theah->queueEvent($event);
        }

        $this->cards->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
        $this->theah->queueEvent($equipAttachmentEvent);

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("attachmentEquipped");
    }

    public function actHighDramaClaimActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanClaim($player_id) == false) {
            throw new \BgaUserException(self::_("Claim Action is not allowed right now."));
        }

        $this->gamestate->nextState("claimActionStart");
    }

    public function actHighDramaClaimActionPerformerChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $activePlayerId = $this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->theah->getCharacterById($id);
        if ($performer->Engaged) {
            throw new \BgaUserException(self::_("Performer cannot Claim because it is engaged."));
        }

        $characters = $this->theah->getCharactersInPlayByPlayerId($activePlayerId);
        
        //Filter out those characters that are not in the city
        $charactersInCity = array_filter($characters, fn($character) => $this->theah->cardInCity($character) );  

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersInCity);

        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException(self::_("Performer is not in the City."));
        }

        $this->globals->set(Game::CLAIMING_PLAYER, $activePlayerId);
        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $claimEvent = $this->theah->createEvent(Events::ClaimOccuring);
        if ($claimEvent instanceof EventClaimOccuring)
        {
            $claimEvent->performerId = $performer->Id;
            $claimEvent->location = $performer->Location;
            $claimEvent->playerId = $this->getActivePlayerId();
            $claimEvent->pressureTypes = $this->theah->getPressureTypesForClaim($performer);
        }
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
            $event = EventFactory::createActionTriggeredEvent($player_id, $action->OwnerId, $actionId);
            $this->theah->eventCheck($event);
            $this->theah->queueEvent($event);
    
            $this->globals->set(GAME::PASS_COUNT, 0);
            $this->gamestate->nextState("inPlayActionChosen");
        }
    }

    public function actHighDramaInPlayActionPerformerChosen(string $ids)
    {
        $playerId = (int)$this->getActivePlayerId();
        $id = json_decode($ids, true)[0];
        $performer = $this->getCardObjectFromDb($id);

        $actionId = $this->globals->get(GAME::CHOSEN_ACTION, '');

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $event = EventFactory::createActionTriggeredEvent($playerId, $performer->Id, $actionId);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::PASS_COUNT, 0);
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

        if ($action->RequiresPerformerSelected)
        {
            $this->gamestate->nextState("requiresPerformerSelected");
        }
        else
        {
            $this->gamestate->nextState("inHandActionChosen");
        }
    }

    public function actHighDramaInHandActionPerformerChosen(string $ids)
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $id = json_decode($ids, true)[0];
        $performer = $this->getCardObjectFromDb($id);

        $actionId = $this->globals->get(GAME::CHOSEN_ACTION, '');
        $action = $this->theah->getInHandActionById($actionId);

        $this->globals->set(GAME::CHOSEN_PERFORMER, $performer->Id);

        $discount = $this->theah->getActionFromHandDiscount($performer);
        $this->globals->set(Game::DISCOUNT, $discount);

        $this->gamestate->nextState("inHandActionPerformerChosen");
    }

    public function actPayForInHandAction(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();

        $performerId = $this->globals->get(GAME::CHOSEN_PERFORMER);
        $performer = $this->theah->getCharacterById($performerId);

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

        $cost = $risk->WealthCost;
        $discount = $this->globals->get(Game::DISCOUNT);

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            if ($card == null)
                throw new \BgaUserException(sprintf(self::_("Card #%d not found."), $cardId));

            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
            $this->theah->queueEvent($event);
        }

        $this->notifyAllPlayers("message", clienttranslate('${player_name} has decided to perform the In-Hand Action from <strong>${card_name}</strong>.'), [
            'i18n' => ['card_name'],
            "player_name" => $this->getActivePlayerName(),
            "card_name" => $risk->Name,
        ]);

        $event = EventFactory::createActionTriggeredEvent($playerId, $performer->Id, $actionId);
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $risk->Id);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::PASS_COUNT, 0);
        $this->gamestate->nextState("actionPaidFor");
    }

    public function actHighDramaChallengeActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanChallenge($player_id) == false) {
            throw new \BgaUserException(self::_("Challenge Action is not allowed right now."));
        }

        $this->gamestate->nextState("challengeActionStart");
    }

    public function actHighDramaChallengeActionPerformerChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $activePlayerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->theah->getCharacterById($id);
        if ( ! $performer->canChallenge()) {
            throw new \BgaUserException(self::_("Performer cannot Challenge."));
        }

        $characters = $this->theah->getCharactersInPlayByPlayerId($activePlayerId);
        
        //Filter out those characters that are not in the city
        $charactersInCity = array_filter($characters, fn($character) => $this->theah->cardInCity($character) );  

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersInCity);

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

    public function actHighDramaChallengeActionTargetChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];

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

        $owner = $technique->getOwningCharacter($this->theah);
        if ($owner->Id != $performer->Id) {
            throw new \BgaUserException(self::_("Technique does not belong to the Performer."));
        }

        $this->globals->set(GAME::CHOSEN_TECHNIQUE, $technique->Id);

        $this->gamestate->nextState("techniqueActivated");
    }

    public function actHighDramaChallengeActionActivateTechnique_Pass()
    {
        $this->gamestate->nextState("pass");
    }

    public function actHighDramaChallengeActionAccept()
    {
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        $event = EventFactory::createChallengeAcceptedEvent($performer->Id, $target->Id);
        $this->theah->eventCheck($event);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} ACCEPTS The Challenge.'), [
            "player_name" => $this->getActivePlayerName(),
        ]);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, true);

        $this->gamestate->nextState("");
    }

    public function actHighDramaChallengeActionReject()
    {
        $performer = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_PERFORMER));
        $target = $this->getCardObjectFromDb($this->globals->get(GAME::CHOSEN_TARGET));

        $event = EventFactory::createChallengeRejectedEvent($performer->Id, $target->Id);
        $this->theah->eventCheck($event);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} REJECTS The Challenge.'), [
            "player_name" => $this->getActivePlayerName(),
        ]);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, false);

        $this->gamestate->nextState("");
    }

    public function actHighDramaChallengeActionIntervene(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $this->theah->buildCity();
        $character = $this->theah->getCardById($id);

        $target = $this->theah->getCardById($this->globals->get(GAME::CHOSEN_TARGET));
        if ($target->Location != $character->Location) {
            throw new \BgaUserException(self::_("Character is not at the same location as the target."));
        }    

        if( ! $character->canIntervene()) {
            throw new \BgaUserException(self::_("Character cannot Intervene."));
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

        $engageEvent = EventFactory::createCardEngagedEvent($playerId, $character->Id);
        $this->theah->eventCheck($engageEvent);
        
        $this->theah->queueEvent($interveneEvent);
        $this->theah->queueEvent($engageEvent);

        $this->globals->set(GAME::CHALLENGE_ACCEPTED, true);

        $this->gamestate->nextState("");
    }    

    public function actDuelActionChooseTechnique()
    {
        $this->theah->buildCity();
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT * FROM duel_round where duel_id = $duelId AND round = $round";
        $round = $this->getObjectListFromDB($sql)[0];

        $actorId = $round['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);

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

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);

        $technique = $this->theah->getTechniqueById($techniqueId);
        if ($technique == null) {
            throw new \BgaUserException(self::_("Technique not found."));
        }
        
        if ( ! $this->theah->isTechniqueOwnedByCharacter($technique, $actor)) {
            throw new \BgaUserException(self::_("Technique does not belong to the Actor."));
        }

        $adversaryId = $this->getDuelOpponentId($actorId);
        $adversary = $this->theah->getCharacterById($adversaryId);

        $resolveEvent = $this->theah->createEvent(Events::ResolveTechnique);
        if ($resolveEvent instanceof EventResolveTechnique)
        {
            $resolveEvent->playerId = $playerId;
            $resolveEvent->actorId = $actor->Id;
            $resolveEvent->adversaryId = $adversary->Id;
            $resolveEvent->techniqueId = $technique->Id;
        }
        $this->theah->eventCheck($resolveEvent);
        $this->theah->queueEvent($resolveEvent);

        $threatEvent = $this->theah->createEvent(Events::DuelCalculateTechniqueValues);
        if ($threatEvent instanceof EventDuelCalculateTechniqueValues)
        {
            $threatEvent->actorId = $actor->Id;
            $threatEvent->adversaryId = $adversary->Id;
            $threatEvent->techniqueId = $technique->Id;
        }
        $this->theah->eventCheck($threatEvent);
        $this->theah->queueEvent($threatEvent);

        $this->gamestate->nextState("techniqueChosen");
    }

    public function actDuelActionResolveTechnique_01013 (bool $useThrust)
    {
        $this->theah->buildCity();
        $techniqueId = $this->globals->get(Game::CHOSEN_TECHNIQUE);
        $technique = $this->theah->getTechniqueById($techniqueId);

        if ($technique instanceof Technique_01013)
        {
            $technique->UseThrust = $useThrust;
            $owner = $this->theah->getCharacterById($technique->OwnerId);
            $owner->IsUpdated = true;        
        }

        $this->gamestate->nextState("");
    }

    public function actDuelActionGamble()
    {
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

        if ($card instanceof IHasManeuvers)
        {
            $this->gamestate->nextState("useManeuver");
        }
        else
        {
            //Remove card from hand
            $this->cards->moveCard($card->Id, Game::LOCATION_PURGATORY, $playerId);

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
        $this->cards->moveCard($card->Id, Game::LOCATION_PURGATORY, $playerId);

        $this->gamestate->nextState("maneuverDeclined");
    }

    public function actDuelPayForManeuverFromCombatCard(string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $actor = $this->theah->getCharacterById($actorId);

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
            $totalWealth += in_array("Wealth", $payCard->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $payCard->Id);
            $this->theah->queueEvent($event);
        }

        $adversaryId = $this->getDuelOpponentId($actorId);
        $adversary = $this->theah->getCharacterById($adversaryId);

        $resolveEvent = $this->theah->createEvent(Events::ResolveManeuver);
        if ($resolveEvent instanceof EventResolveManeuver)
        {
            $resolveEvent->playerId = $playerId;
            $resolveEvent->adversaryId = $adversary->Id;
            $resolveEvent->maneuverId = $maneuver->Id;
        }
        $this->theah->eventCheck($resolveEvent);
        $this->theah->queueEvent($resolveEvent);

        $threatEvent = $this->theah->createEvent(Events::DuelCalculateManeuverValues);
        if ($threatEvent instanceof EventDuelCalculateManeuverValues)
        {
            $threatEvent->actorId = $actor->Id;
            $threatEvent->adversaryId = $adversary->Id;
            $threatEvent->maneuverId = $maneuver->Id;
        }
        $this->theah->eventCheck($threatEvent);
        $this->theah->queueEvent($threatEvent);

        //Remove card from hand
        $this->cards->moveCard($card->Id, Game::LOCATION_PURGATORY, $playerId);

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

        $cards = $this->cards->getCardsOnTop(2, $deckName);
        if ($cards[0]['id'] != $id && $cards[1]['id'] != $id) {
            throw new \BgaUserException(self::_("Chosen card is not one of the two on top."));
        }

        //Get the card not chosen from cards array
        $notChosenCard = $cards[0]['id'] == $id ? $cards[1] : $cards[0];
        $this->cards->insertCardOnExtremePosition($notChosenCard['id'], $deckName, false);

        $this->globals->set(Game::CHOSEN_CARD, $id);

        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);

        //Set that the player has gambled
        $sql = "UPDATE duel_round set gambled = 1 WHERE duel_id = $duelId AND round = $round";
        $this->DbQuery($sql);

        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->getObjectListFromDB($sql)[0];

        $actorId = $result['actor_id'];
        $adversaryId = $this->getDuelOpponentId($actorId);

        $event = $this->theah->createEvent(Events::DuelPlayerGambled);
        if ($event instanceof EventDuelPlayerGambled)
        {
            $event->playerId = $playerId;
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->chosenCardId = $id;
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);        

        $card = $this->getCardObjectFromDb($id);
        $this->cards->moveCard($card->Id, Game::LOCATION_PURGATORY, $playerId);

        $this->gamestate->nextState();
    }
    

    public function actDuelDoneRound()
    {
        $duelId = $this->globals->get(Game::DUEL_ID);
        $round = $this->globals->get(Game::DUEL_ROUND);    
        $type = $this->globals->get(Game::DUEL_TYPE);

        $sql = "SELECT actor_id, combat_card_id FROM duel_round where duel_id = $duelId AND round = $round";
        $roundInfo = $this->getObjectListFromDB($sql)[0];
        $actorId = $roundInfo['actor_id'];
        $cardId = $roundInfo['combat_card_id'];

        if ($round == 1 && $type != Game::VLADISLAV_DUEL_TYPE)
        {
            //Check to see if a combat card was played
            if ($cardId == null)
            {
                throw new \BgaUserException(self::_("For the first round, you must either gamble or a combat card must be played."));
            }
        }
        
        $event = $this->theah->createEvent(Events::DuelActionsDone);
        if ($event instanceof EventDuelActionsDone)
        {
            $event->playerId = $this->getActivePlayerId();
            $event->actorId = $actorId;
            $event->adversaryId = $this->getDuelOpponentId($actorId);
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
        $actionId = $this->globals->get(Game::CHOSEN_ACTION, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardPass($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId);
    }

    public function actFromCardWithId(int $id)
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::CHOSEN_ACTION, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithId($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $id);
    }

    public function actFromCardWithIds(string $ids)
    {
        $this->theah->buildCity();
        $ids = json_decode($ids, true);

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::CHOSEN_ACTION, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithIds($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $ids);
    }

    public function actFromCardWithLocations(string $locations)
    {
        $this->theah->buildCity();
        $locations = json_decode($locations, true);

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $actionId = $this->globals->get(Game::CHOSEN_ACTION, '');
        $card = $this->theah->getCardById($sourceId);
        $card->actFromCardWithIds($this, $this->gamestate->state_id(), $this->gamestate->state()['name'], $actionId, $locations);
    }

    public function actReactionForState(string $reactionId)
    {
        $this->theah->buildCity();

        $sourceId = $this->globals->get(Game::TRANSITION_SOURCE_ID);
        $internalId = $this->globals->get(Game::TRANSITION_INTERNAL_ID);
        $state = $this->gamestate->state_id();

        if ($sourceId == Game::THEAH_ID)
        {
            $reaction = $this->theah->getReactionById($internalId);
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
            $totalWealth += in_array("Wealth", $payCard->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException(sprintf(self::_("Cost of Card is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
        }

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $payCard = $this->getCardObjectFromDb($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $payCard->Id);
            $this->theah->queueEvent($event);
        }

        $announcement = $reaction->getReactionAnnouncement($this, $this->gamestate->state_id(), $internalId, $reactionId);
        $this->notifyAllPlayers("message", clienttranslate('${player_name} ${announcement}'), [
            "player_name" => $this->getActivePlayerName(),
            "announcement" => $announcement,
        ]);

        $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
        $this->theah->queueEvent($event);

        $reaction->reactionPaidFor($this, $this->gamestate->state_id(), $internalId, $reactionId);

        $this->gamestate->nextState("paid");
   }

}