<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardPlayed;

trait EventHandler
{
    public function handleEvent($event)
    {
        switch (true) {
            case $event instanceof EventCityCardAddedToLocation:
                // Add the card to the world
                $this->cards[] = $event->card;

                $event->card->Location = $event->location;
                $event->card->IsUpdated = true;
               
                // Notify players that card has been played
                $this->game->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('${card_name} added to ${location} from the city deck'), [
                    "card_name" => $event->card->Name,
                    "location" => $event->location,
                    "card" => $event->card->getPropertyArray()
                ]);

                break;

            case $event instanceof EventSchemeCardPlayed:
                unset($this->approachCards[$event->scheme->Id]);
                $this->cards[] = $event->scheme;

                $event->scheme->Location = $event->location;
                $event->scheme->IsUpdated = true;

                // Notify players that the player will play the selected scheme
                $this->game->notifyAllPlayers("playApproachScheme", clienttranslate('${player_name} will play ${scheme_name} as their Approach Scheme.'), [
                    "player_name" => $event->playerName,
                    "scheme_name" => "<span style='font-weight:bold'>{$event->scheme->Name}</span>",
                    "player_id" => $event->playerId,
                    "scheme" => $event->scheme->getPropertyArray(),
                ]);

                break;

            case $event instanceof EventApproachCharacterPlayed:
                unset($this->approachCards[$event->character->Id]);
                $this->cards[] = $event->character;                

                $event->character->Location = $event->location;
                $event->character->IsUpdated = true;

                // Notify players that the player will play the selected character
                $this->game->notifyAllPlayers("playApproachCharacter", clienttranslate('${player_name} will play ${character_name} as their Approach Character.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $event->playerName,
                    "character_name" => "<span style='font-weight:bold'>{$event->character->Name}</span>",
                    "character" => $event->character->getPropertyArray(),
                ]);

                break;
        }
    }
}