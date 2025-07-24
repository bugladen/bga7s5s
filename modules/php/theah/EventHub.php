<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterPutIntoApproachDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelActionsDone;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelPlayerGambled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhasePlayerPassed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTakeReknownForControlledLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlunderPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlunderPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

trait EventHub
{
    public function handleEvent($event)
    {
        if ($event->canceled)
            return;

        switch (true) {
            case $event instanceof EventActionUsed:
                $handler = function (Theah $theah, EventActionUsed $event)
                {
                    $theah->game->notifyAllPlayers("actionUsed", '', [
                        'playerId' => $event->playerId,
                        'ownerId' => $event->ownerId,
                        'actionId' => $event->actionId,
                        'used' => $event->used,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventApproachCharacterPlayed:
                $handler = function (Theah $theah, EventApproachCharacterPlayed $event)
                {
                    //Update the character's location in the DB
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->characterId, Game::LOCATION_PLAYER_HOME, $event->playerId);

                    $character = $theah->getCardById($event->characterId);
                    $theah->upsertCard($character);

                    $character->Location = Game::LOCATION_PLAYER_HOME;
                    $character->IsUpdated = true;

                    // Notify players of selected character
                    $theah->game->notifyAllPlayers("approachCharacterPlayed", clienttranslate('${player_name} plays <strong>${character_name}</strong> as their Approach Character.'), [
                        'i18n' => ['character_name'],
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "character_name" => $character->Name,
                        "character" => $character->getPropertyArray($theah->game),
                        ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventAttachmentEquipped:
                $handler = function (Theah $theah, EventAttachmentEquipped $event)
                {
                    $performer = $theah->getCardById($event->characterId);                    
                    $attachment = $theah->getCardById($event->attachmentId);
                    // If the attachment is not in the world (came from the City Deck), add it
                    if ($attachment == null)
                    {
                        $attachment = $theah->game->getCardObjectFromDb($event->attachmentId);
                        $theah->addCardToWorld($attachment);
                    }

                    if ($performer instanceof Character) {
                        $performer->addAttachment($attachment);
                        $modifiedResolve = $performer->ModifiedResolve;
                        $modifiedCombat = $performer->ModifiedCombat;
                        $modifiedFinesse = $performer->ModifiedFinesse;
                        $modifiedInfluence = $performer->ModifiedInfluence;
                    }

                    if ($attachment instanceof Attachment) {                        
                        $attachment->ControllerId = $event->playerId;
                        $attachment->AttachedToId = $performer->Id;
                        $attachment->Location = $performer->Location;
                        $attachment->IsUpdated = true;
                    }
                    
                    // Notify players of attachment equipped
                    $message = clienttranslate('${player_name} equipped <strong>${attachment_name}</strong> to <strong>${performer_name}</strong>. ');
                    if ($event->asAction)
                        $message .= clienttranslate('This was done at a discount of ${discount} for a cost of ${cost} Wealth.');
                    $theah->game->notifyAllPlayers("attachmentEquipped", $message, [
                        'i18n' => ['attachment_name', 'performer_name'],
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "attachment_name" => $attachment->Name,
                        "performer_name" => $performer->Name,
                        "discount" => $event->discount,
                        "cost" => $event->cost,
                        "attachment" => $attachment->getPropertyArray($theah->game),
                        "performerId" => $performer->Id,
                        "modifiedResolve" => $modifiedResolve,
                        "modifiedCombat" => $modifiedCombat,
                        "modifiedFinesse" => $modifiedFinesse,
                        "modifiedInfluence" => $modifiedInfluence,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventAttachmentUnequipped:
                $handler = function (Theah $theah, EventAttachmentUnequipped $event)
                {
                    $attachment = $theah->getAttachmentById($event->attachmentId);
                    $attachment->AttachedToId = 0;
                    $attachment->IsUpdated = true;

                    $character = $theah->getCharacterById($event->characterId);
                    $character->removeAttachment($attachment);
                    $modifiedResolve = $character->ModifiedResolve;
                    $modifiedCombat = $character->ModifiedCombat;
                    $modifiedFinesse = $character->ModifiedFinesse;
                    $modifiedInfluence = $character->ModifiedInfluence;

                    $theah->game->notifyAllPlayers("attachmentUnequipped", clienttranslate('${player_name} unequipped <strong>${attachment_name}</strong> from <strong>${character_name}</strong>.'), [
                        'i18n' => ['attachment_name', 'character_name'],
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "attachment_name" => $attachment->Name,
                        "character_name" => $character->Name,
                        "attachmentId" => $attachment->Id,
                        "characterId" => $character->Id,
                        "modifiedResolve" => $modifiedResolve,
                        "modifiedCombat" => $modifiedCombat,
                        "modifiedFinesse" => $modifiedFinesse,
                        "modifiedInfluence" => $modifiedInfluence,
                    ]);

                    if ($character->ModifiedResolve <= 0 && ! $character->IsDying)
                    {
                        $destroyEvent = EventFactory::createCharacterDestroyedEvent($character->ControllerId, $character->Id, sprintf($this->game->translate("Has unequipped %s"), $attachment->Name));
                        $this->queueEvent($destroyEvent);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardDrawn:
                $event->card->Location = Game::LOCATION_HAND;
                $event->card->IsUpdated = true;

                $this->game->notifyPlayer($event->playerId, "drawCard", clienttranslate('Private: You drew <strong>${card_name}</strong> because of ${reason}.'), [
                    'i18n' => ['card_name', 'reason'],
                    "card_name" => $event->card->Name,
                    "card" => $event->card->getPropertyArray($this->game),
                    "reason" => $event->reason,
                ]);

                // Notify players that card has been added to hand
                $this->game->notifyAllPlayers("drawCardMessage", clienttranslate('${player_name} drew a card into their Faction Hand because of ${reason}.'), [
                    'i18n' => ['reason'],
                    "playerId" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "reason" => $event->reason,
                ]);
                break;

            case $event instanceof EventCardAddedToCityDeck:
                $handler = function (Theah $theah, EventCardAddedToCityDeck $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Location = Game::LOCATION_CITY_DECK;
                    $card->IsUpdated = true;

                    $deck = $theah->game->getGameDeckObject();
                    $deck->insertCardOnExtremePosition($event->cardId, Game::LOCATION_CITY_DECK, $event->onTop);

                    $message = $event->onTop 
                        ? clienttranslate('${player_name} added <strong>${card_name}</strong> to the top of the City Deck.') 
                        : clienttranslate('${player_name} sunk <strong>${card_name}</strong> to the bottom of the City Deck.');

                    $this->game->notifyAllPlayers("cardAddedToCityDeck", $message, [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "cardId" => $card->Id,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardAddedToFactionDeck:
                $handler = function (Theah $theah, EventCardAddedToFactionDeck $event)
                {
                    $deckName = $theah->game->getPlayerFactionDeckName($event->playerId);
                    $card = $theah->getCardById($event->cardId);
                    $card->Location = $deckName;
                    $card->IsUpdated = true;

                    $deck = $theah->game->getGameDeckObject();
                    $deck->insertCardOnExtremePosition($event->cardId, $deckName, $event->onTop);

                    $message = $event->onTop 
                        ? clienttranslate('${player_name} added <strong>${card_name}</strong> to the top of their Faction Deck.') 
                        : clienttranslate('${player_name} sunk <strong>${card_name}</strong> to the bottom of their Faction Deck.');

                    $this->game->notifyAllPlayers("message", $message, [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardAddedToHand:
                $handler = function (Theah $theah, EventCardAddedToHand $event)
                {
                    //Move card in DB
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, Game::LOCATION_HAND, $event->playerId);

                    $card = $theah->game->getCardObjectFromDb($event->cardId);
                    $card->Location = Game::LOCATION_HAND;
                    $theah->game->updateCardObjectInDb($card);

                    $card->IsUpdated = true;
                    $theah->upsertCard($card);
    
                    // Notify players that card has been added to hand
                    $this->game->notifyAllPlayers("cardAddedToHand", clienttranslate('${player_name} added <strong>${card_name}</strong> to their Faction Hand.'), [
                        'i18n' => ['card_name'],
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "card" => $card->getPropertyArray($this->game),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardAddedToCityDiscardPile:
                $handler = function (Theah $theah, EventCardAddedToCityDiscardPile $event)
                {
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, Game::LOCATION_CITY_DISCARD);

                    $card = $theah->getCardById($event->cardId);
                    $card->Location = Game::LOCATION_CITY_DISCARD;
                    $card->ControllerId = 0;
                    $card->IsUpdated = true;

                    $this->game->notifyAllPlayers("cardAddedToCityDiscardPile", clienttranslate('<strong>${card_name}</strong> added to City Discard pile from ${location}.'), [
                        'i18n' => ['card_name', 'location'],
                        "card_name" => $card->Name,
                        "cardId" => $card->Id,
                        "card" => $card->getPropertyArray($theah->game),
                        "location" => $event->fromLocation,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardDiscardedFromHand:
                $handler = function (Theah $theah, EventCardDiscardedFromHand $event)
                {
                    $discardPileName = $theah->game->getPlayerDiscardDeckName($event->playerId);

                    $card = $theah->getCardById($event->cardId);
                    $card->Location = $discardPileName;
                    $card->IsUpdated = true;

                    $deckObject = $theah->game->getGameDeckObject();
                    $deckObject->moveCard($card->Id, $discardPileName);

                    // Notify players that card has been discarded from hand
                    $theah->game->notifyAllPlayers("cardDiscardedFromHand", clienttranslate('${player_name} discarded <strong>${card_name}</strong>.'), [
                        'i18n' => ['card_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "playerId" => $event->playerId,
                        "card" => $card->getPropertyArray($theah->game),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardDiscardedFromPlay:
                $handler = function (Theah $theah, EventCardDiscardedFromPlay $event)
                    {
                        $discardPileName = $theah->game->getPlayerDiscardDeckName($event->ownerId);

                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->cardId, $discardPileName);
    
                        $card = $theah->getCardById($event->cardId);
                        $card->Location = $discardPileName;
                        $card->IsUpdated = true;
    
                    $this->game->notifyAllPlayers("cardDiscardedFromPlay", clienttranslate('<strong>${card_name}</strong> discarded from ${location}.'), [
                        'i18n' => ['card_name', 'location'],
                        "playerId" => $event->ownerId,
                        "card_name" => $card->Name,
                        "cardId" => $event->cardId,
                        "location" => $event->fromLocation,
                    ]);
                    };
                    $handler($this, $event);
                    break;

            case $event instanceof EventCardEngaged:
                $handler = function (Theah $theah, EventCardEngaged $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Engaged = true;
                    $card->IsUpdated = true;

                    $theah->game->notifyAllPlayers("cardEngaged", clienttranslate('${player_name} Engages <strong>${card_name}</strong>.'), [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "cardId" => $card->Id,
                    ]);
                };
                $handler($this, $event);
                break;                

            case $event instanceof EventCardEngarded:
                $handler = function (Theah $theah, EventCardEngarded $event)
                {   
                    $card = $theah->getCardById($event->cardId);
                        $card->Engaged = false;
                    $card->IsUpdated = true;
    
                    $this->game->notifyAllPlayers("cardEngarded", clienttranslate('${player_name} En gardes <strong>${card_name}</strong>.'), [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "cardId" => $card->Id,
                    ]);
                };
                $handler($this, $event);
                break;                

            case $event instanceof EventCardMoved:
                $handler = function (Theah $theah, EventCardMoved $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Location = $event->toLocation;
                    $card->IsUpdated = true;
                    if ($card instanceof Character) 
                    {
                        $card->Engaged = $event->engage;

                        foreach ($card->Attachments as $attachmentId) {
                            $attachment = $theah->getCardById($attachmentId);
                            $attachment->Location = $event->toLocation;
                            $attachment->IsUpdated = true;
                        }
                    }

                    $this->game->notifyAllPlayers("cardMoved", clienttranslate('<strong>${card_name}</strong> moved from ${fromLocation} to ${toLocation}.'), [
                        'i18n' => ['card_name', 'fromLocation', 'toLocation'],
                        "card_name" => $card->Name,
                        "cardId" => $card->Id,
                        "fromLocation" => $event->fromLocation,
                        "toLocation" => $event->toLocation,
                        "engage" => $event->engage
                    ]);
                };
                $handler($this, $event);    
                break;

            case $event instanceof EventCardRemovedFromCityDiscardPile:
                $handler = function (Theah $theah, EventCardRemovedFromCityDiscardPile $event)
                {
                    $card = $theah->getCardById($event->cardId);

                    $theah->game->notifyAllPlayers("cardRemovedFromCityDiscardPile", clienttranslate('${card_name} removed from City Discard pile.'), [
                        'i18n' => ['card_name'],
                        "card_name" => $card->Name,
                        "card" => $card->getPropertyArray($theah->game),
                    ]);    
                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventCardRemovedFromPlayerDiscardPile:
                $handler = function (Theah $theah, EventCardRemovedFromPlayerDiscardPile $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $this->game->notifyAllPlayers("cardRemovedFromPlayerDiscardPile", clienttranslate('<strong>${card_name}</strong> removed from ${player_name}\'s discard pile.'), [
                        'i18n' => ['card_name'],
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                        "card" => $card->getPropertyArray($this->game),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCharacterInfluenceModified:
                $handler = function (Theah $theah, EventCharacterInfluenceModified $event)
                {
                    $character = $theah->getCharacterById($event->CharacterId);
                    $character->ModifiedInfluence = $event->NewInfluence;
                    $character->IsUpdated = true;

                    $theah->game->notifyAllPlayers("characterInfluenceModified", clienttranslate('The influence of ${character_name} went from ${oldInfluence} to ${newInfluence}.'), [
                        'i18n' => ['character_name'],
                        "character_name" => $character->Name,
                        "characterId" => $character->Id,
                        "oldInfluence" => $event->OldInfluence, 
                        "newInfluence" => $event->NewInfluence,
                    ]);
                };
                $handler($this, $event);
                break;

                case $event instanceof EventCharacterMustered:
                    $handler = function (Theah $theah, EventCharacterMustered $event)
                    {
                        //Update the character's location in the DB
                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->characterId, $event->location, $event->playerId);

                        $character = $theah->getCardById($event->characterId);
                        $character->Location = $event->location;
                        $character->ControllerId = $event->playerId;
                        $character->IsUpdated = true;
                        $theah->upsertCard($character);        
        
                        // Notify players of mustered character
                        $theah->game->notifyAllPlayers("characterMustered", clienttranslate('${player_name} musters <strong>${character_name}</strong> at ${location}.'), [
                            'i18n' => ['character_name', 'location'],
                            "player_id" => $event->playerId,
                            "player_name" => $theah->game->getPlayerNameById($event->playerId),
                            "character_name" => $character->Name,
                            "location" => $event->location,
                            "character" => $character->getPropertyArray($theah->game),
                        ]);
                    };
                    $handler($this, $event);
                    break;

            case $event instanceof EventCharacterRecruited:
                $character = $this->cards[$event->character->Id];
                $character->ControllerId = $event->playerId;
                $character->IsUpdated = true;

                // Notify players of recruited character
                $this->game->notifyAllPlayers("characterRecruited", clienttranslate('${player_name} recruits <strong>${character_name}</strong> at a discount of ${discount} for a cost of ${cost} Wealth.'), [
                    'i18n' => ['character_name'],
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_name" => $event->character->Name,
                    "characterId" => $event->character->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
                ]);
                break;

            case $event instanceof EventCityCardAddedToLocation:
                $handler = function (Theah $theah, EventCityCardAddedToLocation $event)
                {
                    $card = $theah->game->getCardObjectFromDb($event->cardId);
                    $card->Location = $event->location;
                    $card->IsUpdated = true;
                    $theah->upsertCard($card);

                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, $event->location);
                    
                    // Notify players that card has been played
                    $theah->game->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('<strong>${card_name}</strong> added to ${location} from the city deck'), [
                        'i18n' => ['card_name', 'location'],
                        "card_name" => $card->Name,
                        "location" => $event->location,
                        "card" => $card->getPropertyArray($theah->game)
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationClaimed:
                $handler = function (Theah $theah, EventLocationClaimed $event)
                {
                    $this->cityLocations[$event->location]->Controller = $event->playerId;

                    $this->game->notifyAllPlayers("locationClaimed", clienttranslate('${player_name} Claimed <strong>${location_name}</strong>.'), [
                        'i18n' => ['card_name', 'location_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "location_name" => $event->location,
                        "playerId" => $event->playerId,
                        "location" => $event->location,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationPressured:
                $handler = function (Theah $theah, EventLocationPressured $event)
                {
                    $performer = $theah->getCharacterById($event->performerId);
                    $theah->game->notifyAllPlayers("locationPressured", clienttranslate('${player_name} chose <strong>${performer_name}</strong> to ${result} Pressure <strong>${location}</strong>.
                    <br>Pressure Type: ${pressureType}
                    <br>Influence Totals: ${totals}'), [
                        'i18n' => ['performer_name', 'location', 'pressureType'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "performer_name" => $performer->Name,
                        "result" => $event->success ? clienttranslate("successfully") : clienttranslate("unsuccessfully"),
                        "totals" => $event->totalsExplanation,
                        "pressureType" => $event->pressureType,
                        "playerId" => $event->playerId,
                        "location" => $performer->Location,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventManeuverUsed:
                $handler = function (Theah $theah, EventManeuverUsed $event)
                {
                    $theah->game->notifyAllPlayers('maneuverUsed', '', [
                        "playerId" => $event->playerId,
                        "ownerId" => $event->ownerId,
                        "maneuverId" => $event->maneuverId,
                        "used" => $event->used,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventReknownAddedToCard:
                $handler = function (Theah $theah, EventReknownAddedToCard $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Reknown += $event->amount;
                    $card->IsUpdated = true;

                    // Notify players that the player has lost reknown
                    $theah->game->notifyAllPlayers("reknownUpdatedOnCard", clienttranslate('${player_name} added ${amount} Reknown to ${card_name} (${total} now on card).'), [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "cardId" => $card->Id,
                        "card_name" => $card->Name,
                        "amount" => $event->amount,
                        "total" => $card->Reknown
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventReknownRemovedFromCard:
                $handler = function (Theah $theah, EventReknownRemovedFromCard $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Reknown -= $event->amount;
                    if ($card->Reknown < 0)
                        $card->Reknown = 0;
                    $card->IsUpdated = true;

                    // Notify players that the player has lost reknown
                    $theah->game->notifyAllPlayers("reknownUpdatedOnCard", clienttranslate('${player_name} removed ${amount} Reknown from ${card_name} (${total} now on card).'), [
                        'i18n' => ['card_name'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "cardId" => $card->Id,
                        "card_name" => $card->Name,
                        "amount" => $event->amount,
                        "total" => $card->Reknown
                    ]);
                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventPlayerLosesReknown:
                $handler = function (Theah $theah, EventPlayerLosesReknown $event)
                {
                    $playerId = $event->playerId;
                    $db = $theah->getDBObject();
                    $reknown = $db->getPlayerReknown($playerId);
                    if ($reknown > 0) 
                    {
                        $reknown -= $event->amount;
                        if ($reknown < 0)
                        {
                            //Adjust the amount to be the amount that will be lost
                            $event->amount = $reknown;
                            $reknown = 0;
                        }

                        $db->setPlayerReknown($playerId, $reknown);

                        // Notify players that the player has lost reknown
                        $this->game->notifyAllPlayers("playerReknownUpdated", clienttranslate('${player_name} loses ${amount} reknown (now at ${total}).'), [
                            "player_id" => $event->playerId,
                            "player_name" => $this->game->getPlayerNameById($playerId),
                            "amount" => $event->amount,
                            "total" => $reknown,
                        ]);
                    }   
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlayerGainsReknown:
                $handler = function (Theah $theah, EventPlayerGainsReknown $event)
                {
                    $playerId = $event->playerId;
                    $db = $theah->getDBObject();
                    $reknown = $db->getPlayerReknown($playerId);
                    $reknown += $event->amount;
                    $db->setPlayerReknown($playerId, $reknown);

                    // Notify players that the player has gained reknown
                    $this->game->notifyAllPlayers("playerReknownUpdated", clienttranslate('${player_name} gains ${amount} reknown (now at ${total}).'), [
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($playerId),
                        "amount" => $event->amount,
                        "total" => $reknown,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventReactionUsed:
                $handler = function (Theah $theah, EventReactionUsed $event)
                {
                    $theah->game->notifyAllPlayers("reactionUsed", '', [
                        "playerId" => $event->playerId,
                        "ownerId" => $event->ownerId,
                        "reactionId" => $event->reactionId,
                        "used" => $event->used,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventReknownAddedToLocation:
                //Update the reknown for the location in the database
                $reknown = $this->game->getReknownForLocation($event->location) + $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

                $this->cityLocations[$event->location]->Reknown += $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notifyAllPlayers("reknownAddedToLocation", clienttranslate('${amount} reknown ADDED to ${location} ${source}.'), [
                    'i18n' => ['location'],
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
                    'i18n' => ['location'],
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
                $this->game->notifyAllPlayers("approachSchemePlayed", clienttranslate('${player_name} plays <strong>${scheme_name}</strong> as their Approach Scheme.'), [
                    'i18n' => ['scheme_name'],
                    "player_name" => $event->playerName,
                    "scheme_name" => $event->scheme->Name,
                    "player_id" => $event->playerId,
                    "scheme" => $event->scheme->getPropertyArray($this->game),
                ]);

                break;

            case $event instanceof EventSchemeMovedToCity:
                $event->scheme->Location = $event->location;
                $event->scheme->IsUpdated = true;
                
                //Card is now in city
                $this->cards[$event->scheme->Id] = $event->scheme;
                break;

            case $event instanceof EventTechniqueUsed:
                $handler = function (Theah $theah, EventTechniqueUsed $event)
                {
                    $theah->game->notifyAllPlayers("techniqueUsed", '', [
                        "playerId" => $event->playerId,
                        "ownerId" => $event->ownerId,
                        "techniqueId" => $event->techniqueId,
                        "used" => $event->used,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventChallengeIssued:
                $handler = function ($theah, EventChallengeIssued $event)
                {
                    $challenger = $theah->cards[$event->challengerId];
                    $challenger->addCondition(GAME::DUEL_CHALLENGER);
                    
                    $defender = $theah->cards[$event->defenderId];
                    $defender->addCondition(GAME::DUEL_DEFENDER);

                    $statUsed = $theah->game->globals->get(Game::CHALLENGE_STAT);
                    
                    $message = clienttranslate('${player_name} has chosen <strong>${challenger_name}</strong> to Challenge <strong>${defender_name}</strong>. ');

                    switch ($statUsed)
                    {
                        case Game::STAT_COMBAT:
                            $message .= clienttranslate('<br>The Duel will use the Combat stat. ');
                            break;
                        case Game::STAT_FINESSE:
                            $message .= clienttranslate('<br>The Duel will use the Finesse stat. ');
                            break;
                        case Game::STAT_INFLUENCE:
                            $message .= clienttranslate('<br>The Duel will use the Influence stat. ');
                            break;
                            
                    }
                    $technique = null;
                    if ($event->activatedTechniqueId)
                    {
                        $message .= clienttranslate('<br>${player_name} will activate Technique <strong>${technique_name}</strong> for the Challenge.');
                        $technique = $theah->getTechniqueById($event->activatedTechniqueId);
                    }
                    else
                    {
                        $message .= clienttranslate('<br>No Technique will be activated for the Challenge.');
                    }
                                            
                    $theah->game->notifyAllPlayers("challengeIssued", $message, [
                        'i18n' => ['challenger_name', 'defender_name', 'technique_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "challenger_name" => $challenger->Name,
                        "defender_name" => $defender->Name,
                        "technique_name" => $technique?->Name,
                        "challengerId" => $challenger->Id,
                        "defenderId" => $defender->Id,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventChallengerSwapped:
                $handler = function ($theah, EventChallengerSwapped $event)
                {
                    $challenger = $theah->getCharacterById($event->oldChallengerId);
                    $newChallenger = $theah->getCharacterById($event->newChallengerId);

                    $theah->game->notifyAllPlayers("challengerSwapped", clienttranslate('${player_name} has swapped <strong>${challenger_name}</strong> for <strong>${new_challenger_name}</strong>.'), [
                        'i18n' => ['challenger_name', 'new_challenger_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "challenger_name" => $challenger->Name,
                        "new_challenger_name" => $newChallenger->Name,
                        "oldChallengerId" => $event->oldChallengerId,
                        "newChallengerId" => $event->newChallengerId,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCharacterPutIntoApproachDeck:
                $handler = function ($theah, EventCharacterPutIntoApproachDeck $event)
                {
                    $deck = $theah->game->getGameDeckObject($event->playerId);
                    $deck->moveCard($event->characterId, Game::LOCATION_APPROACH, $event->playerId);

                    $character = $theah->getCharacterById($event->characterId);
                    $character->Location = Game::LOCATION_APPROACH;
                    $character->OwnerId = $event->playerId;
                    $character->ControllerId = $event->playerId;
                    $character->IsUpdated = true;

                    $theah->game->notifyAllPlayers("message", clienttranslate('<strong>${character_name}</strong> has been put into ${player_name}\'s Approach Deck.'), [
                        'i18n' => ['character_name'],
                        "character_name" => $character->Name,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId)
                    ]);

                    $theah->game->notifyPlayer($event->playerId, "approachCardsReceived", 
                        clienttranslate(''), [
                            "cards" => [$character->getPropertyArray($theah->game)]
                        ]);
        
                };
                $handler($this, $event);
                break;
                    
            case $event instanceof EventCharacterIntervened:
                $handler = function ($theah, EventCharacterIntervened $event)
                {
                    $oldTarget = $theah->cards[$event->oldTargetId];
                    $newTarget = $theah->cards[$event->newTargetId];
                    $this->game->notifyAllPlayers("characterIntervened", clienttranslate('${player_name} has chosen to have <strong>${intervener_name}</strong> INTERVENE in the Challenge in place of <strong>${target_name}</strong>.'), [
                        'i18n' => ['intervener_name', 'target_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "intervener_name" => $newTarget->Name,
                        "target_name" => $oldTarget->Name,
                        "oldTargetId" => $oldTarget->Id,
                        "newTargetId" => $newTarget->Id,
                    ]);
                };
                $handler($this, $event);
                break;
                        
            case $event instanceof EventDefenderSwapped:
                $handler = function ($theah, EventDefenderSwapped $event)
                {
                    $defender = $theah->getCharacterById($event->oldDefenderId);
                    $newDefender = $theah->getCharacterById($event->newDefenderId);

                    $theah->game->notifyAllPlayers("defenderSwapped", clienttranslate('${player_name} has swapped <strong>${defender_name}</strong> for <strong>${new_defender_name}</strong>.'), [
                        'i18n' => ['defender_name', 'new_defender_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "defender_name" => $defender->Name,
                        "new_defender_name" => $newDefender->Name,
                        "oldDefenderId" => $event->oldDefenderId,
                        "newDefenderId" => $event->newDefenderId,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventGenerateChallengeThreat:
                $handler = function ($theah, EventGenerateChallengeThreat $event)
                {
                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", $theah->game->translate($explanation));
                    }
                    
                    $theah->game->globals->set(Game::CHALLENGER_THREAT, $event->actorThreat);
                    $theah->game->globals->set(Game::DEFENDER_THREAT, $event->adversaryThreat);
                    $actor = $theah->cards[$event->actorId];
                    $adversary = $theah->cards[$event->adversaryId];
                    
                    $theah->game->notifyAllPlayers("message", clienttranslate('${actor_name} has ${actor_threat} total Threat for the Challenge. ${adversary_name} has ${adversary_threat} total Threat.'), [
                        "actor_name" => $actor->Name,
                        "actor_threat" => $event->actorThreat,
                        "adversary_name" => $adversary->Name,
                        "adversary_threat" => $event->adversaryThreat,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventTechniqueActivated:
                $handler = function ($theah, EventTechniqueActivated $event)
                {
                    $technique = $theah->getTechniqueById($event->techniqueId);
                    $technique->setUsed($theah, true);

                    $theah->game->notifyAllPlayers("techniqueActivated", clienttranslate('${player_name} has activated the Technique [${technique_name}].'), [
                        'i18n' => ['technique_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "technique_name" => $technique->Name,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventResolveTechnique:
                $handler = function ($theah, EventResolveTechnique $event)
                {
                    $technique = $theah->getTechniqueById($event->techniqueId);
                    $technique->setUsed($theah, true);
                    if ($event->inDuel)
                    {
                        $duelId = $theah->game->globals->get(Game::DUEL_ID);
                        $round = $theah->game->globals->get(Game::DUEL_ROUND);
                        $name = substr(addslashes($technique->Name), 0, 500);
                        $sql = "INSERT INTO duel_round_technique (duel_id, round, technique_id, technique_name) VALUES ($duelId, $round, '{$event->techniqueId}', '$name')";
                        $event->theah->game->DbQuery($sql);    
                    }
                };
                $handler($this, $event);
                break;
                
            case $event instanceof EventDuelCalculateTechniqueValues:
                //This is a post-handling event
                $handler = function ($theah, EventDuelCalculateTechniqueValues $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $actor = $theah->cards[$event->actorId];
                    $technique = $theah->getTechniqueById($event->techniqueId);
                    $owningCard = $technique->getOwningCard($theah);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "technique", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";
                    if ($results["riposte"] > 0) 
                    {
                        $riposteText = $theah->game->translate("Riposte");
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    if ($results["parry"] > 0) 
                    {
                        $parryText = $theah->game->translate("Parry");
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    if ($results["thrust"] > 0) 
                    {
                        $thrustText = $theah->game->translate("Thrust");
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>Challenger Threat went from %s to %s. "), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"]);
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>Defender Threat went from %s to %s. "), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"]);

                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", clienttranslate('<strong>${character_name}</strong> adds the Technique [<strong>${effect_name}</strong>]. ${effects}'), [
                        'i18n' => ['character_name', 'effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "technique",
                        "character_name" => $actor->Name,
                        "card_name" => $owningCard->Name,
                        "effect_name" => $technique->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                        "wounds" => $results["wounds"],
                    ]);                    
                };    
                $handler($this, $event);
                break;

            case $event instanceof EventDuelGetCostForManeuverFromHand:
                //This is a post-handling event, so after any chance of cost modification, 
                //we can set the cost in the globals to set up for argsDuelPayForManeuverFromCombatCard
                $handler = function (Theah $theah, EventDuelGetCostForManeuverFromHand $event)
                {
                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", $theah->game->translate($explanation), []);
                    }

                    $theah->game->globals->set(Game::CHOSEN_CARD_COST, $event->cost);
                    $theah->game->globals->set(Game::DISCOUNT, $event->discount);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventManeuverActivated:
                $handler = function ($theah, EventManeuverActivated $event)
                {
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    $maneuver->setUsed($theah, true);

                    $theah->game->notifyAllPlayers("maneuverActivated", clienttranslate('${player_name} has activated the Maneuver [${maneuver_name}].'), [
                        'i18n' => ['maneuver_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "maneuver_name" => $maneuver->Name,
                    ]);
                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventResolveManeuver:
                $handler = function (Theah $theah, EventResolveManeuver $event)
                {
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    $maneuver->setUsed($theah, true);
                    $owningCard = $maneuver->getOwningCard($theah);
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $name = substr(addslashes($owningCard->Name) . ":" . addslashes($maneuver->Name), 0, 500);
                    $sql = "INSERT INTO duel_round_maneuver (duel_id, round, maneuver_id, maneuver_name) VALUES ($duelId, $round, '{$event->maneuverId}', '$name')";
                    $event->theah->game->DbQuery($sql);    
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelCalculateManeuverValues:
                //This is a post-handling event
                $handler = function ($theah, EventDuelCalculateManeuverValues $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $actor = $theah->cards[$event->actorId];
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    $maneuverCard = $maneuver->getOwningCard($theah);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "maneuver", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";
                    $riposteText = $theah->game->translate("Riposte");  
                    if ($results["riposte"] > 0) 
                    {
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    $parryText = $theah->game->translate("Parry");
                    if ($results["parry"] > 0) 
                    {
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    $thrustText = $theah->game->translate("Thrust");
                    if ($results["thrust"] > 0) 
                    {
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= "<p>" . sprintf($theah->game->translate("Challenger Threat went from %s to %s. "), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"]);
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= "<p>" . sprintf($theah->game->translate("Defender Threat went from %s to %s. "), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"]);

                    $message = clienttranslate('${character_name} activated the Maneuver <strong>${effect_name}</strong> ${effects}');
                    if (! $maneuverCard instanceof Character)
                    {
                        $message = clienttranslate('${character_name} has activated the Maneuver <strong>${effect_name}</strong> from <strong>${card_name}</strong> ${effects}');
                    }

                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", $message, [
                        'i18n' => ['character_name', 'effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "maneuver",
                        "character_name" => $actor->Name,
                        "effect_name" => $maneuver->Name,
                        "card_name" => $maneuverCard->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                        "wounds" => $results["wounds"],
                    ]);                    
                };    
                $handler($this, $event);
                break;    

            case $event instanceof EventDuelCalculateCombatCardStats:
                $handler = function ($theah, EventDuelCalculateCombatCardStats $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $card = $theah->game->getCardObjectFromDb($event->combatCardId);
                    $playerId = $card->ControllerId;
                    $playerName = $theah->game->getPlayerNameById($playerId);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "combat", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";  
                    $riposteText = $theah->game->translate("Riposte");
                    if ($results["riposte"] > 0) 
                    {
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    $parryText = $theah->game->translate("Parry");
                    if ($results["parry"] > 0) 
                    {
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    $thrustText = $theah->game->translate("Thrust");
                    if ($results["thrust"] > 0) 
                    {
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= "<p>" . sprintf($theah->game->translate("Challenger Threat went from %s to %s. "), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"]);
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= "<p>" . sprintf($theah->game->translate("Defender Threat went from %s to %s. "), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"]);
                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", clienttranslate('${player_name} has played <strong>${effect_name}</strong> as their Combat Card. ${effects}'), [
                        'i18n' => ['effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "combat",
                        "player_name" => $playerName,
                        "playerId" => $playerId,
                        "effect_name" => $card->Name,
                        "combatCard" => $card->getPropertyArray($theah->game),
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "gambled" => $event->gambled,
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                        "wounds" => $results["wounds"],
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelNewRound:
                $handler = function (Theah $theah, EventDuelNewRound $event)
                {
                    $playerName = $theah->game->getPlayerNameById($event->playerId);
                    $actor = $theah->getCardById($event->actorId);
                    $challenger = $theah->getCardById($event->challengerId);
                    $defender = $theah->getCardById($event->defenderId);
                    $theah->game->notifyAllPlayers("newDuelRound", clienttranslate('DUEL ROUND #${round} HAS STARTED for ${player_name} and their ${role} character <strong>${character_name}</strong>.'), [
                        'i18n' => ['role', 'character_name', 'challengerName', 'defenderName'],
                        "player_name" => $playerName,
                        "role" => $event->round % 2 == 1 ? "Defending" : "Challenging",
                        "character_name" => $actor->Name,
                        "round" => $event->round,
                        "playerId" => $event->playerId,
                        "challengerId" => $event->challengerId,
                        "defenderId" => $event->defenderId,
                        "actorId" => $event->actorId,
                        "actor" => $actor->getPropertyArray($theah->game),
                        "challengerName" => $challenger->Name,
                        "defenderName" => $defender->Name,
                        "startingChallengerThreat" => $event->challengerThreat,
                        "startingDefenderThreat" => $event->defenderThreat,
                        "endingChallengerThreat" => $event->challengerThreat,
                        "endingDefenderThreat" => $event->defenderThreat,
                        "wounds" => $event->wounds
                    ]);            
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelPlayerGambled:
                $handler = function(Theah $theah, EventDuelPlayerGambled $event) {
                    $card = $theah->game->getCardObjectFromDb($event->chosenCardId);
                    $theah->upsertCard($card);
                    $theah->game->notifyAllPlayers("message", clienttranslate('${player_name} has gambled with <strong>${card_name}</strong>.'), [
                        'i18n' => ['card_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_name" => $card->Name,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelActionsDone:
                $handler = function (Theah $theah, EventDuelActionsDone $event)
                {
                    $theah->game->notifyAllPlayers("message", clienttranslate('${player_name} is done with their actions for the round.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelEnd:
                $handler = function (Theah $theah, EventDuelEnd $event)
                {
                    //Cards are going to be read from the database, as they may be in the locker and not available to Theah
                    
                    $challenger = $theah->getCardById($event->challengerId);
                    $challenger->removeCondition(GAME::DUEL_CHALLENGER);
                    $theah->game->updateCardObjectInDb($challenger);
                    
                    $defender = $theah->getCardById($event->defenderId);
                    $defender->removeCondition(GAME::DUEL_DEFENDER);
                    $theah->game->updateCardObjectInDb($defender);

                    $theah->game->notifyAllPlayers("duelEnd", clienttranslate('The Duel has ended.'), [
                        "challengerId" => $event->challengerId,
                        "defenderId" => $event->defenderId,
                        "challengingPlayerId" => $event->challengingPlayerId,
                        "defendingPlayerId" => $event->defendingPlayerId
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventChallengeRejected:
                $handler = function (Theah $theah, EventChallengeRejected $event)
                {
                    $challenger = $theah->getCardById($event->challengerId);
                    $challenger->removeCondition(GAME::DUEL_CHALLENGER);
                    $theah->game->updateCardObjectInDb($challenger);
                    
                    $defender = $theah->getCardById($event->targetId);
                    $defender->removeCondition(GAME::DUEL_DEFENDER);
                    $theah->game->updateCardObjectInDb($defender);

                    $theah->game->notifyAllPlayers("challengeRejected", clienttranslate('${player_name} REFUSES The Challenge.'), [
                        "player_name" => $theah->game->getPlayerNameById($defender->ControllerId),
                        "challengerId" => $event->challengerId,
                        "defenderId" => $event->targetId,
                    ]);                       
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCharacterDestroyed:
                $handler = function ($theah, EventCharacterDestroyed $event)
                {
                    $character = $theah->getCardById($event->characterId);
                    $locker = $theah->game->getPlayerLockerName($character->ControllerId);
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->characterId, $locker);

                    //Card has been moved to the locker, so recreate it because it has no memory of past state
                    $fullClassname = get_class($character);
                    $pos = strrpos($fullClassname, '\\');
                    $className = substr($fullClassname, $pos + 2);
                    $character = $theah->game->instantiateCard($className);            
                    $character->setId($event->characterId);
                    $character->Location = $locker;
                    $character->IsUpdated = true;
                    $theah->upsertCard($character);

                    $theah->game->notifyAllPlayers("characterDestroyed", clienttranslate('<strong>${target_name}</strong> has been destroyed and sent to the locker due to: ${reason} '), [
                        'i18n' => ['target_name', 'reason'],
                        "playerId" => $event->playerId,
                        "target_name" => $character->Name,
                        "characterId" => $event->characterId,
                        "reason" => $event->reason,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventSchemeSentToLocker:
                $handler = function ($theah, EventSchemeSentToLocker $event)
                {
                    $scheme = $theah->cards[$event->schemeId];
                    $locker = $theah->game->getPlayerLockerName($scheme->ControllerId);
                    $scheme->Location = $locker;

                    $theah->game->notifyAllPlayers("schemeSentToLocker", clienttranslate('<strong>${scheme_name}</strong> has been sent to the locker.'), [
                        'i18n' => ['scheme_name'],
                        "playerId" => $scheme->ControllerId,
                        "scheme_name" => $scheme->Name,
                        "scheme" => $scheme->getPropertyArray($theah->game),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventHighDramaPhasePlayerPassed:
                $handler = function ($theah, EventHighDramaPhasePlayerPassed $event)
                {
                    // Notify all players about the choice to pass.
                    $this->game->notifyAllPlayers("message", clienttranslate('${player_name} has passed for their High Drama Action.'), [
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventHighDramaPhaseEnd:
                $handler = function ($theah, EventHighDramaPhaseEnd $event)
                {
                    $theah->game->notifyAllPlayers("highDramaPhaseEnd", clienttranslate('<strong>END OF HIGH DRAMA PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlunderPhaseBegin:
                $handler = function ($theah, EventPlunderPhaseBegin $event)
                {
                    $theah->game->notifyAllPlayers("plunderPhaseBegin", clienttranslate('<strong>BEGINNING OF PLUNDER PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlayerTakeReknownForControlledLocation:
                $handler = function ($theah, EventPlayerTakeReknownForControlledLocation $event)
                {
                    $theah->game->notifyAllPlayers("message", clienttranslate('${player_name} controls ${location_name} and will receive ${reknown} Reknown.'), [
                        'i18n' => ['location_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "location_name" => $event->location,
                        "reknown" => $event->reknown,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlunderPhaseEnd:
                $handler = function ($theah, EventPlunderPhaseEnd $event)
                {
                    $theah->game->notifyAllPlayers("plunderPhaseEnd", clienttranslate('<strong>END OF PLUNDER PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventThreatModified:
                $handler = function ($theah, EventThreatModified $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $challenger = $theah->getCardById($event->challengerId);
                    $defender = $theah->getCardById($event->defenderId);

                    $db = $theah->getDBObject();
                    $db->updateRoundThreats($duelId, $round, $event->challengerThreat, $event->defenderThreat);

                    $result = $db->getRoundThreats($duelId, $round);
                    $endingChallengerThreat = $result['ending_challenger_threat'];
                    $endingDefenderThreat = $result['ending_defender_threat'];
                    $wounds = $result['wounds_taken'];

                    $theah->game->notifyAllPlayers("updateRoundThreats", clienttranslate(
                        'Threat for <strong>${challenger_name}</strong> has been modified by ${challenger_modification}.
                        <br>
                        Threat for <strong>${defender_name}</strong> has been modified by ${defender_modification}.
                        <br>
                        <strong>Current Challenger Threat:</strong> ${challenger_threat}
                        <br>
                        <strong>Current Defender Threat:</strong> ${defender_threat}'), [
                        'i18n' => ['challenger_name', 'defender_name'],
                        "challenger_name" => $challenger->Name,
                        "defender_name" => $defender->Name,
                        "challenger_modification" => $event->challengerThreat,
                        "defender_modification" => $event->defenderThreat,
                        "challenger_threat" => $endingChallengerThreat,
                        "defender_threat" => $endingDefenderThreat,
                        "wounds" => $wounds,
                        "round" => $round,
                    ]);

                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventDuskPhaseBegin:
                $handler = function ($theah, EventDuskPhaseBegin $event)
                {
                    $theah->game->notifyAllPlayers("duskPhaseBegin", clienttranslate('<strong>BEGINNING OF DUSK PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuskPhaseEnd:
                $handler = function ($theah, EventDuskPhaseEnd $event)
                {
                    $theah->game->notifyAllPlayers("duskPhaseEnd", clienttranslate('<strong>END OF DUSK PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuskEndOfDay:
                $handler = function ($theah, EventDuskEndOfDay $event)
                {
                    $theah->game->notifyAllPlayers("duskEndOfDay", clienttranslate('<strong>END OF DAY</strong>'), []);
                };
                $handler($this, $event);
                break;
    
        }

    }
}