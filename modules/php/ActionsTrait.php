<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

trait ActionsTrait
{
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
        $this->theah->buildCity();
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

    public function actPlanningPhase_01044(int $id)
    {
        $this->theah->buildCity();
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
        $this->theah->buildCity();
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

        // Notify players
        $this->notifyAllPlayers("message_01045", clienttranslate('${player_name} chose ${card_name} to move from the City Discard Pile to the top of the City Deck.'), [
            "player_name" => $playerName,
            "card_name" => "<span style='font-weight:bold'>{$card->Name}</span>",
            "player_id" => $playerId,
            "card" => $card->getPropertyArray(),
        ]);

        $this->theah->queueEvent($removeEvent);
        $this->theah->queueEvent($addEvent);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_1(string $locations)
    {
        $this->theah->buildCity();
        $location = json_decode($locations, true)[0];
        
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Adding Reknown to Location";
        }
        $this->theah->queueEvent($event);

        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_1', 
            clienttranslate('You have chosen to place reknown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            "location" => $location
        ]);

        $this->gamestate->nextState("reknownPlaced");
    }

    public function actPlanningPhase_01125_1_Pass()
    {
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_1_Pass', 
            clienttranslate('You have chosen to pass placing reknown onto a location.  Per The Boar\'s Guile you will now choose a city location to move a Reknown FROM.'), []);

        $this->gamestate->nextState("pass");
    }

    public function actPlanningPhase_01125_2(string $locations)
    {
        $this->theah->buildCity();
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $locationReknownName = $this->getReknownLocationName($location);
        $reknown = $this->globals->get($locationReknownName);
        if ($reknown <= 0) {
            throw new \BgaUserException("{$location} does not have any reknown to move.");
        }
        
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
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
        $this->theah->buildCity();
        $location = json_decode($locations, true)[0];

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->playerId = $this->getActivePlayerId();
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
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
            "character" => "<span style='font-weight:bold'>{$character->Name}</span>",
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
        $this->theah->buildCity();
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
                $event->source = "Leshiye of the Woods: Adding Reknown to Location";
            }
            $this->theah->eventCheck($event);        
        }

        //Get the chosen scheme card for the player
        $sql = "SELECT selected_scheme_id FROM player WHERE player_id = $playerId";
        $selectedSchemeId = $this->getUniqueValueFromDB($sql);
        $scheme = $this->getCardObjectFromDb($selectedSchemeId);

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
            "card_name" => '<span style="font-weight:bold">Leshiye of the Woods</span>',
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
                $event->source = "Leshiye of the Woods: Adding Reknown to Location";
            }
            $this->theah->queueEvent($event);
        }

        // Move leshiye of the woods to the chosen location
        $sql = "SELECT selected_scheme_id FROM player WHERE player_id = $playerId";
        $selectedSchemeId = $this->getUniqueValueFromDB($sql);
        $scheme = $this->getCardObjectFromDb($selectedSchemeId);

        $this->cards->moveCard($selectedSchemeId, $leshiyeLocation, $playerId);
        $this->theah->queueEvent($schemeMoveEvent);

        // Go back and finish running the Scheme events
        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01144_1(string $locations)
    {
        $this->theah->buildCity();
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
        $this->theah->queueEvent($event);

        $this->globals->set(GAME::CHOSEN_LOCATION, $location);

        // Get all the reknown to compare
        $players = $this->getCollectionFromDb("SELECT player_id, player_score score FROM player ORDER BY player_score DESC");
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
        $this->theah->buildCity();
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

        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actHighDramaBeginning_01144(int $recruitId, string $payWithCards)
    {
        $this->theah->buildCity();
        $character = $this->getCardObjectFromDb($recruitId);
        if ($character == null)
        {
            throw new \BgaUserException("Character not found.");
        }
        if ( ! $character instanceof CityCharacter)
        {
            throw new \BgaUserException("Character is not a City Character.");
        }

        $discount = $this->globals->get(Game::RECRUIT_DISCOUNT);        

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

        //Check to see if the cards used to pay can be moved to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);

            $event = $this->theah->createEvent(Events::CardAddedToPlayerDiscardPile);
            if ($event instanceof EventCardAddedToPlayerDiscardPile) {
                $event->playerId = $playerId;
                $event->card = $card;
            }
            $this->theah->eventCheck($event);
        }

        //Check to see if the character can be recruited
        $recruitCharacterEvent = $this->theah->createEvent(Events::CharacterRecruited);
        if ($recruitCharacterEvent instanceof EventCharacterRecruited) {
            $recruitCharacterEvent->character = $character;
            $recruitCharacterEvent->playerId = $playerId;
            $recruitCharacterEvent->discount = $discount;
            $recruitCharacterEvent->cost = $cost;
        }
        $this->theah->eventCheck($recruitCharacterEvent);

        //Move the cards used to pay to the player's discard pile
        foreach ($cardIds as $cardId) {
            $card = $this->getCardObjectFromDb($cardId);
            $this->cards->moveCard($cardId, $this->getPlayerDiscardDeckName($playerId));

            $event = $this->theah->createEvent(Events::CardAddedToPlayerDiscardPile);
            if ($event instanceof EventCardAddedToPlayerDiscardPile) {
                $event->playerId = $playerId;
                $event->card = $card;
            }
            $this->theah->queueEvent($event);
        }

        //Recruit the character
        $this->theah->queueEvent($recruitCharacterEvent);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01150(string $locations)
    {
        $this->theah->buildCity();
        $playerName = $this->getActivePlayerName();

        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $removeEvent = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($removeEvent instanceof EventReknownRemovedFromLocation) {
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

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("message", clienttranslate('${player_name} passes.'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("");
    }
}