<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
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
        $locations = json_decode($locations, true);
        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
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
        $playerId = $this->getActivePlayerId();

        //Move card in DB
        $this->cards->moveCard($id, Game::LOCATION_HAND, $playerId);
        $card = $this->getCardObjectFromDb($id);

        $event = $this->theah->createEvent(Events::CardRemovedFromPlayerDiscardPile);
        if ($event instanceof EventCardRemovedFromPlayerDiscardPile) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::CardAddedToHand);
        if ($event instanceof EventCardAddedToHand) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01045(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        //Move card to top of City Deck
        $this->cards->insertCardOnExtremePosition($id, Game::LOCATION_CITY_DECK, true);
        $card = $this->getCardObjectFromDb($id);

        // Notify players
        $this->notifyAllPlayers("message_01045", clienttranslate('${player_name} chose ${card_name} to move from the City Discard Pile to the top of the City Deck.'), [
            "player_name" => $playerName,
            "card_name" => "<span style='font-weight:bold'>{$card->Name}</span>",
            "player_id" => $playerId,
            "card" => $card->getPropertyArray(),
        ]);

        $event = $this->theah->createEvent(Events::CardRemovedFromCityDiscardPile);
        if ($event instanceof EventCardRemovedFromCityDiscardPile) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::CardAddedToCityDeck);
        if ($event instanceof EventCardAddedToCityDeck) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_1(string $locations)
    {
        $location = json_decode($locations, true)[0];
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_1', 
            clienttranslate('You have chosen to place reknown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            "location" => $location
        ]);
        
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Adding Reknown to Location";
        }
        $this->theah->queueEvent($event);

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
        $location = json_decode($locations, true)[0];

        //Check if the location actually has reknown to move
        $locationReknownName = $this->getReknownLocationName($location);
        $reknown = $this->globals->get($locationReknownName);
        if ($reknown <= 0) {
            throw new \BgaUserException("{$location} does not have any reknown to move.");
        }
        
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_2', 
            clienttranslate('You have chosen to move reknown from ${location}.  You must now choose a location to move the Reknown TO.'), [
            "location" => $location
        ]);
        
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->queueEvent($event);

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
        $this->notifyPlayer($this->getActivePlayerId(), 'message_01125_3', 
            clienttranslate('You have chosen to move reknown to ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
            "location" => $location
        ]);

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = "The Boar's Guile: Moving Reknown from one Location to an adjacent location";
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01125_4(string $ids)
    {
        $id = json_decode($ids, true)[0];
        $playerName = $this->getActivePlayerName();
        $character = $this->getCardObjectFromDb($id);

        $this->notifyAllPlayers('yevgeni_adversary_chosen', 
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

    public function actPlanningPhase_01126_1(string $locations)
    {
        $location = json_decode($locations, true)[0];
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        $this->notifyAllPlayers('message', 
            clienttranslate('${player_name} has chosen ${location} as the Chosen Location for ${card_name}'), [
            "player_name" => $playerName,
            "location" => $location,
            "card_name" => '<span style="font-weight:bold">Leshiye of the Woods</span>',
        ]);

        //Get the chosen scheme card for the player
        $sql = "SELECT selected_scheme_id FROM player WHERE player_id = $playerId";
        $selectedSchemeId = $this->getUniqueValueFromDB($sql);
        $scheme = $this->getCardObjectFromDb($selectedSchemeId);

        if ($scheme instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01126) {
            $scheme->chosenLocation = $location;
            $this->updateCardObjectInDb($scheme);
        }

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01126_2(string $locations)
    {
        $locations = json_decode($locations, true);
        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->location = $location;
                $event->amount = 1;
                $event->source = "Leshiye of the Woods: Adding Reknown to Location";
            }
            $this->theah->queueEvent($event);
        }

        $playerId = $this->getActivePlayerId();

        // Move leshiye of the woods to the chosen location
        $sql = "SELECT selected_scheme_id FROM player WHERE player_id = $playerId";
        $selectedSchemeId = $this->getUniqueValueFromDB($sql);
        $scheme = $this->getCardObjectFromDb($selectedSchemeId);

        $this->cards->moveCard($selectedSchemeId, $scheme->chosenLocation, $playerId);

        $event = $this->theah->createEvent(Events::SchemeMovedToCity);
        if ($event instanceof EventSchemeMovedToCity) {
            $event->scheme = $scheme;
            $event->location = $scheme->chosenLocation;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        // Go back and finish running the Scheme events
        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01150(string $locations)
    {
        $playerName = $this->getActivePlayerName();

        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = $playerName;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->location = Game::LOCATION_CITY_FORUM;
            $event->amount = 1;
            $event->source = $playerName;
        }
        $this->theah->queueEvent($event);

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