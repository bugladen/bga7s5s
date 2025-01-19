<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

trait ActionsTrait
{
    public function actPass(string $transition = ""): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("message", clienttranslate('${player_name} passes.'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState($transition);
    }

    public function actBack(): void
    {
        $this->gamestate->nextState("back");
    }

    public function actPlanningPhase_01016_2_Pass()
    {
        $this->actPass("pass");
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

        $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned'); // deactivate player; if none left, transition to 'deckPicked' state
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

        $removeEvent = $this->theah->createEvent(Events::CardRemovedFromPlayerFactionDeck);
        if ($removeEvent instanceof EventCardRemovedFromPlayerFactionDeck) {
            $removeEvent->card = $card;
            $removeEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($removeEvent);

        $addEvent = $this->theah->createEvent(Events::CardAddedToHand);
        if ($addEvent instanceof EventCardAddedToHand) {
            $addEvent->card = $card;
            $addEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($addEvent);

        //Move card in DB
        $this->cards->moveCard($id, Game::LOCATION_HAND, $playerId);

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

        $addEvent = $this->theah->createEvent(Events::CardAddedToHand);
        if ($addEvent instanceof EventCardAddedToHand) {
            $addEvent->card = $card;
            $addEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($addEvent);

        //Move card in DB
        $this->cards->moveCard($id, Game::LOCATION_HAND, $playerId);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01045(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();
        $card = $this->getCardObjectFromDb($id);

        $removeEvent = $this->theah->createEvent(Events::CardRemovedFromCityDiscardPile);
        if ($removeEvent instanceof EventCardRemovedFromCityDiscardPile) {
            $removeEvent->card = $card;
            $removeEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($removeEvent);

        $addEvent = $this->theah->createEvent(Events::CardAddedToCityDeck);
        if ($addEvent instanceof EventCardAddedToCityDeck) {
            $addEvent->card = $card;
            $addEvent->playerId = $playerId;
        }
        $this->theah->eventCheck($addEvent);

        //Move card to top of City Deck
        $this->cards->insertCardOnExtremePosition($id, Game::LOCATION_CITY_DECK, true);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} chose ${card_name} to move from the City Discard Pile to the top of the City Deck.'), [
            "player_name" => $playerName,
            "card_name" => "<strong>{$card->Name}</strong>",
            "player_id" => $playerId
        ]);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

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
            $event->source = "The Boar's Guile: Adding Reknown to Location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('You have chosen to place reknown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            "location" => $location
        ]);

        $this->gamestate->nextState("reknownPlaced");
    }

    public function actPlanningPhase_01125_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message', 
            clienttranslate('You have chosen to pass placing reknown onto a location.  Per The Boar\'s Guile you will now choose a city location to move a Reknown FROM.'), []);

        $this->gamestate->nextState("pass");
    }

    public function actPlanningPhase_01125_2(string $locations)
    {
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $reknown = $this->getReknownForLocation($location);
        if ($reknown <= 0) {
            throw new \BgaUserException("{$location} does not have any reknown to move.");
        }
        
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_2', 
            clienttranslate('You have chosen to move reknown from ${location}.  You must now choose a location to move the Reknown TO.'), [
            "location" => $location
        ]);
        
        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        $this->gamestate->nextState("locationChosen");
    }

    public function actPlanningPhase_01125_2_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_2_Pass', 
            clienttranslate('You have passed choosing a location to move reknown from.  Per The Boar\'s Guile you must now choose an enemy character to target.'), []);

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

        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_3', 
            clienttranslate('You have chosen to move reknown to ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
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
            clienttranslate('${player_name} has chosen ${character} as Yevgeni\'s Adversary.'), [
            "player_name" => $playerName,
            "character" => "<strong>{$character->Name}</strong>",
            "cardId" => $character->Id,
        ]);

        $character->addCondition("Adversary of Yevgeni");
        $this->updateCardObjectInDb($character);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_4_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_4_Pass', 
            clienttranslate('You have passed choosing a character as an adversary.'), []);

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
            clienttranslate('${player_name} has chosen ${location} as the Chosen Location for ${card_name}'), [
            "player_name" => $playerName,
            "location" => $leshiyeLocation,
            "card_name" => "<strong>Leshiye of the Wood</strong>",
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
                $this->cards->moveCard($card->Id, Game::LOCATION_CITY_DISCARD);

                $discard = $this->theah->createEvent(Events::CardAddedToCityDiscardPile);
                if ($discard instanceof EventCardAddedToCityDiscardPile)
                {
                    $discard->card = $card;
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
        $removeEvent = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($removeEvent instanceof EventReknownRemovedFromLocation) {
            $removeEvent->playerId = $this->getActivePlayerId();
            $removeEvent->location = $location;
            $removeEvent->amount = 1;
            $removeEvent->source = $playerName;
        }
        $this->theah->eventCheck($removeEvent);

        $addEvent = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($addEvent instanceof EventReknownAddedToLocation) {
            $addEvent->playerId = $this->getActivePlayerId();
            $addEvent->location = Game::LOCATION_CITY_FORUM;
            $addEvent->amount = 1;
            $addEvent->source = $playerName;
        }
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

    public function actPlanningPhase_01152_Pass()
    {
        $this->actPass("pass");
    }

    public function actPlanningPhase_01152_2(string $locations)
    {
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $reknown = $this->getReknownForLocation($location);
        if ($reknown <= 0) {
            throw new \BgaUserException("{$location} does not have any reknown to move.");
        }
        
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "Until Morale Improves: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->eventCheck($event);
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        $this->gamestate->nextState("locationChosen");
    }

    public function actPlanningPhase_01152_2_Pass()
    {
        $this->actPass("pass");
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
            throw new \BgaUserException("Character not found.");
        }
        if ( ! $character instanceof CityCharacter)
        {
            throw new \BgaUserException("Character is not a City Character.");
        }

        $discount = $this->globals->get(Game::DISCOUNT);        

        $cost = $character->WealthCost - $discount;
        if ($cost < 0) $cost = 0;

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException("Cost of Mercenary is {$cost}. You selected {$totalWealth} Wealth of cards.");
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
            $this->cards->moveCard($cardId, $this->getPlayerDiscardDeckName($playerId));

            $event = $this->theah->createEvent(Events::CardDiscardedFromHand);
            if ($event instanceof EventCardDiscardedFromHand) {
                $event->playerId = $playerId;
                $event->card = $card;
            }
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
            clienttranslate('${player_name} reveals ${picked_card} randomly from ${chosen_player_name}\'s hand.'), [
            "player_name" => $playerName,
            "chosen_player_name" => "<strong>$chosenPlayerName</strong>",
            "picked_card" => "<strong>{$pickedCard->Name}</strong>",
            "card" => $pickedCard->getPropertyArray(),
        ]);

        $this->gamestate->nextState("");
    }

    public function actHighDramaPass()
    {
        $this->actPass("pass");
    }

    public function actHighDramaMoveActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanMove($player_id) == false) {
            throw new \BgaUserException("Moving is not allowed right now.");
        }

        $this->gamestate->nextState("moveActionStart");
    }

    public function actHighDramaMoveActionPerformerChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $character = $this->getCardObjectFromDb($id);
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $this->globals->set(GAME::CHOSEN_CARD, $character->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaMoveActionDestinationChosen(string $locations)
    {
        $location = json_decode($locations, true)[0];
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $cardId = $this->globals->get(GAME::CHOSEN_CARD);
        $card = $this->getCardObjectFromDb($cardId);       
        $this->cards->moveCard($cardId, $location, $card->ControllerId);

        $this->notifyAllPlayers("message", clienttranslate('${player_name} performed a Move Action.'), [
        "player_name" => $playerName,
        ]);

        $movedHome = $this->theah->createEvent(Events::CardMoved);
        if ($movedHome instanceof EventCardMoved)
        {
            $movedHome->card = $card;
            $movedHome->fromLocation = $card->Location;
            $movedHome->toLocation = $location;
            $movedHome->playerId = $card->ControllerId;
            $movedHome->Engage = $card->Location != Game::LOCATION_PLAYER_HOME;
        }
        $this->theah->eventCheck($movedHome);
        $this->theah->queueEvent($movedHome);

        $this->gamestate->nextState("destinationChosen");
    }

    public function actHighDramaRecruitActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanRecruit($player_id) == false) {
            throw new \BgaUserException("Recruiting is not allowed right now.");
        }

        $this->gamestate->nextState("recruitActionStart");
    }

    public function actHighDramaRecruitActionPerformerChosen(string $ids)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $id = json_decode($ids, true)[0];
        $character = $this->getCardObjectFromDb($id);

        if (!$this->theah->cardInCity($character)) {
            throw new \BgaUserException("Character is not in the City.");
        }

        $characters = $this->theah->getCharactersByPlayerId($playerId);
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  
        $charactersThatCanReruit = [];
        foreach ($characters as $character) {
            $charactersAtLocation = $this->theah->getCharactersAtLocation($character->Location);
            $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return in_array("Mercenary", $character->Traits); });
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }
        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanReruit);
        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException("Character not in a state to recruit mercenaries.");
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
        $performer = $this->getCardObjectFromDb($performerId);

        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        $mercenariesAtLocation = array_filter($charactersAtLocation, function($character) { return in_array("Mercenary", $character->Traits); });        
        $mercenaryIds = array_map(function($character) { return $character->Id; }, $mercenariesAtLocation);
        if (!in_array($recruitId, $mercenaryIds)) {
            throw new \BgaUserException("Chosen character is not a Mercenary at the Performer's Location.");
        }        

        $this->notifyAllPlayers("message", clienttranslate('${player_name} chose ${card_name} to perform a Recruit Action.'), [
            "player_name" => $playerName,
            "card_name" => "<strong>{$performer->Name}</strong>",
        ]);

        if ($discount > 0)
        {
            $this->notifyAllPlayers("message", clienttranslate('${player_name} chose to Parley with ${card_name}.'), [
                "player_name" => $playerName,
                "card_name" => "<strong>{$performer->Name}</strong>",
            ]);
            
            $removeEvent = $this->theah->createEvent(Events::CardEngaged);
            if ($removeEvent instanceof EventCardEngaged) {
                $removeEvent->card = $performer;
                $removeEvent->playerId = $playerId;
            }
            $this->theah->eventCheck($removeEvent);
            $this->theah->queueEvent($removeEvent);
        }

        $this->actRecruitMercenary($recruitId, $payWithCards);
        $this->gamestate->nextState("mercenaryChosen");
    }

    public function actHighDramaEquipActionStart()
    {
        $playerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if (!$this->handHasAttachments($playerId) && !$this->theah->playerCanEquip($playerId)) {
            throw new \BgaUserException("Equipping is not allowed right now.");
        }

        $this->gamestate->nextState("equipActionStart");
    }

    public function actHighDramaEquipActionPerformerChosen(string $ids)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $id = json_decode($ids, true)[0];
        $performer = $this->getCardObjectFromDb($id);

        $characters = $this->theah->getCharactersByPlayerId($playerId);        
        //Filter out those characters that are not in the city
        $characters = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });
        $charactersThatCanEquip = [];
        foreach ($characters as $character) {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0) {
                $charactersThatCanEquip[] = $character;
            }
        }
        $charactersAtHome = $this->theah->getCharactersAtHome($playerId);
        $charactersThatCanEquip = array_merge($charactersThatCanEquip, $charactersAtHome);

        //Select only the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersThatCanEquip);
        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException("Character cannot equip attachments.");
        }

        //Set the discount for equipping.
        $discount = $performer->getEquipDiscount(true);
        $this->globals->set(Game::DISCOUNT, $discount);

        $this->globals->set(GAME::CHOSEN_CARD, $performer->Id);

        $this->gamestate->nextState("performerChosen");
    }

    public function actHighDramaEquipAttachment(int $attachmentId, string $payWithCards)
    {
        $this->theah->buildCity();
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $performerId = $this->globals->get(GAME::CHOSEN_CARD);
        $performer = $this->getCardObjectFromDb($performerId);
        
        $attachment = $this->getCardObjectFromDb($attachmentId);

        //Sanity checks
        if ($attachment->Location == Game::LOCATION_HAND)
        {
            //Get the chosen player's hand
            $handCard = $this->cards->getCard($attachmentId);
            $card = $this->getCardObjectFromDb($handCard['id']);
            if ($card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId) {
                throw new \BgaUserException("Attachment is not in Player's Hand.");
            }
        }
        if ($attachment->Location != Game::LOCATION_HAND)
        {
            $attachmentsAtLocation = $this->theah->getAvailableAttachmentsAtLocation($performer->Location);
            $attachmentIds = array_map(function($attachment) { return $attachment->Id; }, $attachmentsAtLocation);
            if (!in_array($attachmentId, $attachmentIds)) {
                throw new \BgaUserException("Attachment is not at Performer's Location.");
            }
        }
        if (in_array("Armor", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Armor")) {
            throw new \BgaUserException("Character cannot have more than one Armor attachment.");
        }
        if (in_array("Attire", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Attire")) {
            throw new \BgaUserException("Character cannot have more than one Attire attachment.");
        }
        if (in_array("Weapon", $attachment->Traits) && $this->characterHasAttachmentOfType($performer, "Weapon")) {
            throw new \BgaUserException("Character cannot have more than one Weapon attachment.");
        }

        $cost = $attachment->WealthCost;
        $discount = $this->globals->get(Game::DISCOUNT);

        $cardIds = json_decode($payWithCards, true);
        
        //Total up the wealth of the cards to see if player paid correctly
        $totalWealth = 0;
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            //If $card has wealth in its traits, add it to the total wealth
            $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
        }
        if ($totalWealth != $cost) {
            throw new \BgaUserException("Cost of Attachment is {$cost}. You selected {$totalWealth} Wealth of cards.");
        }

        $playerId = $this->getActivePlayerId();

        //Equip the attachment
        $equipAttachmentEvent = $this->theah->createEvent(Events::AttachmentEquipped);
        if ($equipAttachmentEvent instanceof EventAttachmentEquipped) {
            $equipAttachmentEvent->attachment = $attachment;
            $equipAttachmentEvent->performer = $performer;
            $equipAttachmentEvent->playerId = $playerId;
            $equipAttachmentEvent->discount = $discount;
            $equipAttachmentEvent->cost = $cost;
        }
        $this->theah->eventCheck($equipAttachmentEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $this->cards->moveCard($cardId, $this->getPlayerDiscardDeckName($playerId));

            $event = $this->theah->createEvent(Events::CardDiscardedFromHand);
            if ($event instanceof EventCardDiscardedFromHand) {
                $event->playerId = $playerId;
                $event->card = $card;
            }
            //No check needed
            $this->theah->queueEvent($event);
        }

        $this->cards->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
        $this->theah->queueEvent($equipAttachmentEvent);

        $this->gamestate->nextState("attachmentEquipped");
    }

    public function actHighDramaClaimActionStart()
    {
        $player_id = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        if ($this->theah->playerCanClaim($player_id) == false) {
            throw new \BgaUserException("Claim Action is not allowed right now.");
        }

        $this->gamestate->nextState("claimActionStart");
    }

    public function actHighDramaClaimActionPerformerChosen(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $activePlayerId = (int)$this->getActivePlayerId();
        $this->theah->buildCity();

        $performer = $this->getCardObjectFromDb($id);
        if ($performer->Engaged) {
            throw new \BgaUserException("Performer cannot Claim because it is engaged.");
        }

        $characters = $this->theah->getCharactersByPlayerId($activePlayerId);
        
        //Filter out those characters that are not in the city
        $charactersInCity = array_filter($characters, function($character) { return $this->theah->cardInCity($character); });  

        //Select the Ids of the characters
        $characterIds = array_map(function($character) { return $character->Id; }, $charactersInCity);

        if (!in_array($id, $characterIds)) {
            throw new \BgaUserException("Performer is not in the City.");
        }

        //Get an array of players to keep track of their influence at the location 
        $playerInfluences = $this->getCollectionFromDB("SELECT player_id FROM player ORDER BY player_score DESC");
        foreach ($playerInfluences as $playerId => $player) {
            $player["influence"] = 0;
            $playerInfluences[$playerId] = $player;
        }

        //Get the total influence of the characters at the location
        $charactersAtLocation = $this->theah->getCharactersAtLocation($performer->Location);
        foreach ($charactersAtLocation as $character) 
        {
            if (!$character->ControllerId) continue;

            $player = $playerInfluences[$character->ControllerId];
            $player['influence'] += $character->getPressureInfluenceValue();
            $playerInfluences[$character->ControllerId] = $player;
        }

        //Get the player with the most influence
        $maxInfluence = 0;
        $maxPlayerId = 0;
        $totals = "";
        foreach ($playerInfluences as $playerId => $player) {
            $totals .= "{$this->getPlayerNameById($playerId)}:({$player['influence']}) ";
            if ($player['influence'] > $maxInfluence) {
                $maxInfluence = $player['influence'];
                $maxPlayerId = $playerId;
            }
        }

        if ($activePlayerId != $maxPlayerId) {
            throw new \BgaUserException("You do not have the most influence at the location. Totals: {$totals}");
        }

        $this->setControllerForLocation($performer->Location, $activePlayerId);

        $engageEvent = $this->theah->createEvent(Events::CardEngaged);
        if ($engageEvent instanceof EventCardEngaged)
        {
            $engageEvent->card = $performer;
            $engageEvent->playerId = $activePlayerId;
        }
        $this->theah->eventCheck($engageEvent);

        $claimEvent = $this->theah->createEvent(Events::LocationClaimed);
        if ($claimEvent instanceof EventLocationClaimed)
        {
            $claimEvent->performer = $performer;
            $claimEvent->location = $performer->Location;
            $claimEvent->playerId = $activePlayerId;
            $claimEvent->totalsExplanation = $totals;
        }    

        $this->theah->eventCheck($claimEvent);

        $this->theah->queueEvent($engageEvent);
        $this->theah->queueEvent($claimEvent);

        $this->gamestate->nextState("performerChosen");
    }

}