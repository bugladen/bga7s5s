<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;

trait EventHandler
{
    public function handleEvent($event)
    {
        switch (true) {

            case $event instanceof EventApproachCharacterPlayed:
                $this->cards[$event->character->Id] = $event->character;

                $event->character->Location = $event->location;
                $event->character->IsUpdated = true;

                // Notify players of selected character
                $this->game->notifyAllPlayers("playApproachCharacter", clienttranslate('${player_name} plays ${character_name} as their Approach Character.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_name" => "<span style='font-weight:bold'>{$event->character->Name}</span>",
                    "character" => $event->character->getPropertyArray(),
                ]);

                break;

            case $event instanceof EventCardAddedToHand:
                $event->card->Location = Game::LOCATION_HAND;
                $event->card->IsUpdated = true;

                // Notify players that card has been added to hand
                $this->game->notifyAllPlayers("cardAddedToHand", clienttranslate('${player_name} added ${card_name} into their hand.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "card" => $event->card->getPropertyArray(),
                ]);

                break;

            case $event instanceof EventCardRemovedFromCityDiscardPile:
                $this->game->notifyAllPlayers("cardRemovedFromCityDiscardPile", clienttranslate('${card_name} removed from City Discard pile.'), [
                    "card_name" => $event->card->Name,
                    "card" => $event->card->getPropertyArray(),
                ]);

                break;

            case $event instanceof EventCardRemovedFromPlayerDiscardPile:
                $this->game->notifyAllPlayers("cardRemovedFromPlayerDiscardPile", clienttranslate('${card_name} removed from ${player_name}\'s discard pile.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => $event->card->Name,
                    "card" => $event->card->getPropertyArray(),
                ]);

                break;

            case $event instanceof EventCityCardAddedToLocation:
                // Add the card to the world
                $this->cards[$event->card->Id] = $event->card;

                $event->card->Location = $event->location;
                $event->card->IsUpdated = true;
                
                // Notify players that card has been played
                $this->game->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('${card_name} added to ${location} from the city deck'), [
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "location" => $event->location,
                    "card" => $event->card->getPropertyArray()
                ]);

                break;
    
            case $event instanceof EventPlayerLosesReknown:
                $playerId = $event->playerId;
                $reknown = $this->db->getPlayerReknown($playerId);
                if ($reknown > 0) {
                    $reknown -= $event->amount;
                    $this->db->setPlayerReknown($playerId, $reknown);

                    // Notify players that the player has lost reknown
                    $this->game->notifyAllPlayers("playerReknownUpdated", clienttranslate('${player_name} loses ${amount} reknown.'), [
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($playerId),
                        "amount" => $event->amount,
                    ]);
                }   

                break;

            case $event instanceof EventReknownAddedToLocation:

                //Update the reknown for the location in the database
                $locationReknownName = $this->game->getReknownLocationName($event->location);
                $reknown = $this->game->globals->get($locationReknownName) + $event->amount;
                $this->game->globals->set($locationReknownName, $reknown);

                $this->cityLocations[$event->location]->Reknown += $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notifyAllPlayers("reknownAddedToLocation", clienttranslate('${amount} reknown ADDED to ${location} ${source}.'), [
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->source) ? "" : "from {$event->source}",
                ]);

                break;

            case $event instanceof EventReknownRemovedFromLocation:

                //Update the reknown for the location in the database
                $locationReknownName = $this->game->getReknownLocationName($event->location);
                $reknown = $this->game->globals->get($locationReknownName) - $event->amount;
                $this->game->globals->set($locationReknownName, $reknown);

                $this->cityLocations[$event->location]->Reknown -= $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notifyAllPlayers("reknownRemovedFromLocation", clienttranslate('${amount} reknown REMOVED from ${location} ${source}.'), [
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->source) ? "" : "from {$event->source}",
                ]);

                break;

            case $event instanceof EventSchemeCardRevealed:
                $this->cards[$event->scheme->Id] = $event->scheme;

                $event->scheme->Location = $event->location;
                $event->scheme->IsUpdated = true;

                // Notify players of selected scheme
                $this->game->notifyAllPlayers("playApproachScheme", clienttranslate('${player_name} plays ${scheme_name} as their Approach Scheme.'), [
                    "player_name" => $event->playerName,
                    "scheme_name" => "<span style='font-weight:bold'>{$event->scheme->Name}</span>",
                    "player_id" => $event->playerId,
                    "scheme" => $event->scheme->getPropertyArray(),
                ]);

                break;

            }
    }
}