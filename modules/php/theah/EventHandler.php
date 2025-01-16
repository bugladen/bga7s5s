<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

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

            case $event instanceof EventAttachmentEquipped:
                $performer = $this->cards[$event->performer->Id];
                $attachment = $this->cards[$event->attachment->Id];

                $performer->addAttachment($attachment);
                $performer->IsUpdated = true;

                $attachment->ControllerId = $event->playerId;
                $attachment->AttachedToId = $performer->Id;
                $attachment->Location = $performer->Location;
                $attachment->IsUpdated = true;
                
                // Notify players of recruited character
                $this->game->notifyAllPlayers("attachmentEquipped", clienttranslate('${player_name} equipped ${attachment_name} to ${performer_name} at a discount of ${discount} for a cost of ${cost} Wealth.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "attachment_name" => "<span style='font-weight:bold'>{$attachment->Name}</span>",
                    "performer_name" => "<span style='font-weight:bold'>{$performer->Name}</span>",
                    "attachment" => $attachment->getPropertyArray(),
                    "performerId" => $performer->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
                ]);

                break;


            case $event instanceof EventCardDrawn:
                $event->card->Location = Game::LOCATION_HAND;
                $event->card->IsUpdated = true;

                $this->game->notifyPlayer($event->playerId, "drawCard", 'You drew ${card_name} because of ${reason}.', [
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "card" => $event->card->getPropertyArray(),
                    "reason" => $event->reason,
                ]);

                // Notify players that card has been added to hand
                $this->game->notifyAllPlayers("drawCardMessage", clienttranslate('${player_name} drew a card into their hand because of ${reason}.'), [
                    "playerId" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "reason" => $event->reason,
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

            case $event instanceof EventCardAddedToCityDiscardPile:
                $this->game->notifyAllPlayers("cardAddedToCityDiscardPile", clienttranslate('${card_name} added to City Discard pile from ${location}.'), [
                    "card_name" => $event->card->Name,
                    "cardId" => $event->card->Id,
                    "location" => $event->fromLocation,
                ]);
                break;

            case $event instanceof EventCardAddedToPlayerDiscardPile:
                $this->game->notifyAllPlayers('cardAddedToPlayerDiscardPile',
                    clienttranslate('${player_name} discarded ${card_name}.'), [
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => $event->card->Name,
                    "playerId" => $event->playerId,
                    "card" => $event->card->getPropertyArray(),
                ]);
                break;

            case $event instanceof EventCardEngaged:
                $card = $this->cards[$event->card->Id];
                $card->Engaged = true;
                $card->IsUpdated = true;

                $this->game->notifyAllPlayers("cardEngaged", clienttranslate('${player_name} Engages ${card_name}.'), [
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => "<strong>{$event->card->Name}</strong>",
                    "cardId" => $event->card->Id,
                ]);
                break;                

            case $event instanceof EventCardMoved:
                $card = $this->cards[$event->card->Id];
                $card->Location = $event->toLocation;
                if ($card instanceof Character) {
                    $card->Engaged = $event->Engage;

                    foreach ($card->Attachments as $attachmentId) {
                        $attachment = $this->cards[$attachmentId];
                        $attachment->Location = $event->toLocation;
                        $attachment->IsUpdated = true;
                    }
                }
                $card->IsUpdated = true;

                $this->game->notifyAllPlayers("cardMoved", clienttranslate('${card_name} moved from ${fromLocation} to ${toLocation}.'), [
                    "card_name" => "<strong>{$event->card->Name}</strong>",
                    "cardId" => $event->card->Id,
                    "fromLocation" => $event->fromLocation,
                    "toLocation" => $event->toLocation,
                    "engage" => $event->Engage,
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

            case $event instanceof EventCharacterRecruited:
                $character = $this->cards[$event->character->Id];
                $character->ControllerId = $event->playerId;
                $character->IsUpdated = true;

                // Notify players of recruited character
                $this->game->notifyAllPlayers("characterRecruited", clienttranslate('${player_name} recruits ${character_name} at a discount of ${discount} for a cost of ${cost} Wealth.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_name" => "<span style='font-weight:bold'>{$event->character->Name}</span>",
                    "characterId" => $event->character->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
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
                $reknown = $this->game->getReknownForLocation($event->location) + $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

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
                $reknown = $this->game->getReknownForLocation($event->location) - $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

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

            case $event instanceof EventSchemeMovedToCity:
                $event->scheme->Location = $event->location;
                $event->scheme->IsUpdated = true;
                $this->cards[$event->scheme->Id] = $event->scheme;
                break;

            }
    }
}