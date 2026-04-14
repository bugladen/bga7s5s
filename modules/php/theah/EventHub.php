<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Brute;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCalculatePayDiscount;
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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
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
                    $theah->game->notify->all("actionUsed", '', [
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
                    $theah->addCardToWorld($character);

                    $character->Location = Game::LOCATION_PLAYER_HOME;
                    $character->IsUpdated = true;

                    // Notify players of selected character
                    $theah->game->notify->all("approachCharacterPlayed", clienttranslate('${player_name} plays ${character_inject_code} as their Approach Character.'), [
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "character_inject_code" => $character->getInjectCode(),
                        "character" => $character->getPropertyArray($theah->game),
                        ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventAttachmentEquipped:
                $handler = function (Theah $theah, EventAttachmentEquipped $event)
                {
                    $performer = $theah->getCharacterById($event->characterId);                    
                    $attachment = $theah->getAttachmentById($event->attachmentId);

                    //Attachments might not be in the world (came from the City Deck, or created by an action), add it to the world
                    $theah->addCardToWorld($attachment);

                    $performer->addAttachment($theah, $attachment);
                    $modifiedResolve = $performer->ModifiedResolve;
                    $modifiedCombat = $performer->ModifiedCombat;
                    $modifiedFinesse = $performer->ModifiedFinesse;
                    $modifiedInfluence = $performer->ModifiedInfluence;

                    if ($attachment instanceof Attachment) {                        
                        $attachment->ControllerId = $event->playerId;
                        $attachment->AttachedToId = $performer->Id;
                        $attachment->Location = $performer->Location;
                        $attachment->IsUpdated = true;
                    }
                    
                    // Notify players of attachment equipped
                    if ($event->messageHidden)
                    {
                        $message = '${player_name} equipped a card under ${performer_inject_code}. ';
                    }
                    else
                    {
                        $message = clienttranslate('${player_name} equipped ${attachment_inject_code} to ${performer_inject_code}. ');
                        if ($event->asAction)
                        {
                            $message .= clienttranslate('This was done at a cost of ${cost} Wealth (discount of ${discount}).');
                            if ($event->explanations != '')
                            {
                                $message .= clienttranslate('<br>${explanations}');
                            }
                        }    
                    }

                    $deck = $theah->game->getGameDeckObject();
                    $theah->game->notify->all("attachmentEquipped", $message, [
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "attachment_inject_code" => $attachment->getInjectCode(),
                        "performer_inject_code" => $performer->getInjectCode(),
                        "discount" => $event->discount,
                        "cost" => $event->cost,
                        "attachment" => $attachment->getPropertyArray($theah->game),
                        "performerId" => $performer->Id,
                        "modifiedResolve" => $modifiedResolve,
                        "modifiedCombat" => $modifiedCombat,
                        "modifiedFinesse" => $modifiedFinesse,
                        "modifiedInfluence" => $modifiedInfluence,
                        "explanations" => $event->explanations,
                        'handCount' => count($deck->getPlayerHand($event->playerId))
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventAttachmentMoved:
                $handler = function (Theah $theah, EventAttachmentMoved $event)
                {
                    $attachment = $theah->getAttachmentById($event->attachmentId);
                    $attachment->AttachedToId = 0;
                    $attachment->IsUpdated = true;

                    $character = $theah->getCharacterById($event->fromCharacterId);
                    $character->removeAttachment($theah, $attachment);
                    $modifiedResolve = $character->ModifiedResolve;
                    $modifiedCombat = $character->ModifiedCombat;
                    $modifiedFinesse = $character->ModifiedFinesse;
                    $modifiedInfluence = $character->ModifiedInfluence;

                    $attachment->AttachedToId = $event->toCharacterId;
                    $attachment->IsUpdated = true;

                    $theah->game->notify->all("attachmentUnequipped", clienttranslate(''), [
                        "player_id" => $event->playerId,
                        "attachmentId" => $attachment->Id,
                        "characterId" => $character->Id,
                        "modifiedResolve" => $modifiedResolve,
                        "modifiedCombat" => $modifiedCombat,
                        "modifiedFinesse" => $modifiedFinesse,
                        "modifiedInfluence" => $modifiedInfluence,
                    ]);

                    if ($character->Wounds >= $character->ModifiedResolve && ! $character->IsDying)
                    {
                        $character->IsDying = true;
                        $character->IsUpdated = true;
                        $character->unEquipAllAttachments($event->theah);
                        $destroyEvent = EventFactory::createCharacterDestroyedEvent($character->ControllerId, $character->Id, sprintf($this->game->translate("Has unequipped %s"), $attachment->Name));
                        $this->queueEvent($destroyEvent);
                    }

                    $performer = $theah->getCharacterById($event->toCharacterId);
                    $performer->addAttachment($theah, $attachment);
                    $modifiedResolve = $performer->ModifiedResolve;
                    $modifiedCombat = $performer->ModifiedCombat;
                    $modifiedFinesse = $performer->ModifiedFinesse;
                    $modifiedInfluence = $performer->ModifiedInfluence;

                    $attachment->ControllerId = $event->playerId;
                    $attachment->AttachedToId = $performer->Id;
                    $attachment->Location = $performer->Location;
                    $attachment->IsUpdated = true;
                
                    // Notify players of attachment equipped
                    $message = clienttranslate('${player_name} moved ${attachment_inject_code} from ${from_character_code} to ${to_character_code}.');
                    $theah->game->notify->all("attachmentEquipped", $message, [
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "attachment_inject_code" => $attachment->getInjectCode(),
                        "from_character_code" => $character->getInjectCode(),
                        "to_character_code" => $performer->getInjectCode(),
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
                    $character->removeAttachment($theah, $attachment);
                    $modifiedResolve = $character->ModifiedResolve;
                    $modifiedCombat = $character->ModifiedCombat;
                    $modifiedFinesse = $character->ModifiedFinesse;
                    $modifiedInfluence = $character->ModifiedInfluence;

                    if ($attachment->FaceDown)
                    {
                        $message = clienttranslate('[Hidden Card] has been unequipped from ${character_inject_code}.');
                    }
                    else
                    {
                        $message = clienttranslate('${attachment_inject_code} has been unequipped from ${character_inject_code}.');
                    }

                    $theah->game->notify->all("attachmentUnequipped", clienttranslate($message), [
                        "player_id" => $event->playerId,
                        "attachment_inject_code" => $attachment->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                        "attachmentId" => $attachment->Id,
                        "characterId" => $character->Id,
                        "modifiedResolve" => $modifiedResolve,
                        "modifiedCombat" => $modifiedCombat,
                        "modifiedFinesse" => $modifiedFinesse,
                        "modifiedInfluence" => $modifiedInfluence,
                    ]);

                    if ($character->Wounds >= $character->ModifiedResolve && ! $character->IsDying)
                    {
                        $character->IsDying = true;
                        $character->IsUpdated = true;
                        $character->unEquipAllAttachments($event->theah);
                        $destroyEvent = EventFactory::createCharacterDestroyedEvent($character->ControllerId, $character->Id, sprintf($this->game->translate("Has unequipped %s"), $attachment->Name));
                        $this->queueEvent($destroyEvent);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardDrawn:
                $handler = function (Theah $theah, EventCardDrawn $event)
                {
                    $card = $theah->game->playerDrawCard($event->playerId);
                    $card->Location = Game::LOCATION_HAND;
                    $card->IsUpdated = true;
    
                    $deck = $theah->game->getGameDeckObject();
                    $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $event->playerId);
                    $count = count($hand);
    
                    $theah->game->notify->player($event->playerId, "drawCard", clienttranslate('Private: You drew ${card_inject_code} because of ${reason}.'), [
                        'i18n' => ['card_name', 'reason'],
                        "card_inject_code" => $card->getInjectCode(),
                        "card" => $card->getPropertyArray($theah->game),
                        "reason" => $event->reason,
                    ]);
    
                    // Notify players that card has been added to hand
                    $theah->game->notify->all("drawCardMessage", clienttranslate('${player_name} drew a card into their Faction Hand because of ${reason}.'), [
                        'i18n' => ['reason'],
                        "playerId" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "reason" => $event->reason,
                        "count" => $count,
                    ]);
                };
                $handler($this, $event);
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
                        ? clienttranslate('${player_name} added ${card_inject_code} to the top of the City Deck.') 
                        : clienttranslate('${player_name} sunk ${card_inject_code} to the bottom of the City Deck.');

                    $theah->game->notify->all("cardAddedToCityDeck", $message, [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
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
                        ? clienttranslate('${player_name} added ${card_inject_code} to the top of their Faction Deck.') 
                        : clienttranslate('${player_name} sunk ${card_inject_code} to the bottom of their Faction Deck.');

                    $theah->game->notify->all("message", $message, [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
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
                    $theah->addCardToWorld($card);

                    if ($event->hidden)
                    {
                        $theah->game->notify->player($event->playerId, "drawCard", clienttranslate('Private: You added ${card_inject_code} into your hand.'), [
                            "card_inject_code" => $card->getInjectCode(),
                            "card" => $card->getPropertyArray($theah->game),
                        ]);
        
                        // Notify players that card has been added to hand
                        $theah->game->notify->all("drawCardMessage", clienttranslate('${player_name} added a card into their Faction Hand.'), [
                            'i18n' => ['reason'],
                            "playerId" => $event->playerId,
                            "player_name" => $theah->game->getPlayerNameById($event->playerId),
                            "card_inject_code" => $card->getInjectCode(),
                            "count" => count($deck->getPlayerHand($event->playerId)),
                        ]);
                    }
                    else
                    {
                        // Notify players that card has been added to hand
                        $this->game->notify->all("cardAddedToHand", clienttranslate('${player_name} added ${card_inject_code} to their Faction Hand.'), [
                            "player_id" => $event->playerId,
                            "player_name" => $this->game->getPlayerNameById($event->playerId),
                            "card_inject_code" => $card->getInjectCode(),
                            "card" => $card->getPropertyArray($this->game),
                            "handCount" => count($deck->getPlayerHand($event->playerId)),
                        ]);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardAddedToCityDiscardPile:
                $handler = function (Theah $theah, EventCardAddedToCityDiscardPile $event)
                {
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, Game::LOCATION_CITY_DISCARD);

                    $card = $theah->getCardById($event->cardId);
                    if ($card instanceof Character)
                    {
                        //Character has been destroyed, so recreate it because it has no memory of past state
                        $fullClassname = get_class($card);
                        $pos = strrpos($fullClassname, '\\');
                        $className = substr($fullClassname, $pos + 2);
                        $card = $theah->game->instantiateCard($className, $card->Id);            
                        $theah->addCardToWorld($card);
                    }
                    $card->Engaged = false;
                    $card->Location = Game::LOCATION_CITY_DISCARD;
                    $card->ControllerId = 0;
                    $card->IsUpdated = true;

                    $theah->game->notify->all("cardAddedToCityDiscardPile", clienttranslate('${card_inject_code} added to City Discard pile from ${location}.'), [
                        'i18n' => ['location'],
                        "card_inject_code" => $card->getInjectCode(),
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
                    $discardPileName = $theah->game->getPlayerDiscardDeckName($event->ownerId);

                    $card = $theah->getCardById($event->cardId);
                    $card->Location = $discardPileName;
                    $card->IsUpdated = true;

                    $deckObject = $theah->game->getGameDeckObject();
                    $deckObject->moveCard($card->Id, $discardPileName);

                    // Notify players that card has been discarded from hand
                    $message = '${player_name} discarded ${card_inject_code}.';
                    if ($event->AsPlayed)
                        $message = '${player_name} played ${card_inject_code}.';
                    if ($event->AsPayment)
                        $message = '${player_name} discarded ${card_inject_code} as payment.';

                    $theah->game->notify->all("cardDiscardedFromHand", clienttranslate($message), [
                        "player_name" => $theah->game->getPlayerNameById($event->ownerId),
                        "card_inject_code" => $card->getInjectCode(),
                        "playerId" => $event->ownerId,
                        "card" => $card->getPropertyArray($theah->game),
                        "handCount" => count($deckObject->getPlayerHand($event->ownerId)),
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
                    if ($card instanceof Character)
                    {
                        //Character has been destroyed, so recreate it because it has no memory of past state
                        $fullClassname = get_class($card);
                        $pos = strrpos($fullClassname, '\\');
                        $className = substr($fullClassname, $pos + 2);
                        $card = $theah->game->instantiateCard($className, $card->Id);            
                        $theah->addCardToWorld($card);
                    }
                    $card->Location = $discardPileName;
                    $card->Engaged = false;
                    $card->IsUpdated = true;

                    if ($card->FaceDown)
                    {
                        $message = clienttranslate('[Hidden Card] discarded from ${location}.');
                    }
                    else
                    {
                        $message = clienttranslate('${card_inject_code} discarded from ${location}.');
                    }

                    $theah->game->notify->all("cardDiscardedFromPlay", clienttranslate($message), [
                        'i18n' => ['location'],
                        "playerId" => $event->ownerId,
                        "card_inject_code" => $card->getInjectCode(),
                        "cardId" => $event->cardId,
                        "card" => $card->getPropertyArray($theah->game),
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

                    $theah->game->notify->all("cardEngaged", clienttranslate('${player_name} Engages ${card_inject_code}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
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
    
                    $theah->game->notify->all("cardEngarded", clienttranslate('${player_name} En gardes ${card_inject_code}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
                        "cardId" => $card->Id,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardHidden:
                $handler = function (Theah $theah, EventCardHidden $event)
                {
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, Game::LOCATION_PERMANENTLY_HIDDEN);

                    $card = $theah->getCardById($event->cardId);
                    $card->Location = Game::LOCATION_PERMANENTLY_HIDDEN;
                    $card->IsUpdated = true;
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardMoving:
                $handler = function (Theah $theah, EventCardMoving $event)
                {
                    $movedEvent = self::createEvent(Events::CardMoved);
                    if ($movedEvent instanceof EventCardMoved)
                    {
                        $movedEvent->initiatingPlayerId = $event->initiatingPlayerId;
                        $movedEvent->cardId = $event->cardId;
                        $movedEvent->fromLocation = $event->fromLocation;
                        $movedEvent->toLocation = $event->toLocation;
                        $movedEvent->engage = $event->engage;
                        $movedEvent->sourceId = $event->sourceId;
                        $movedEvent->abilityId = $event->abilityId;
                    }
                    $theah->queueEvent($movedEvent);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardMoved:
                $handler = function (Theah $theah, EventCardMoved $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($card->Id, $event->toLocation, $card->ControllerId);
                    $card->Location = $event->toLocation;

                    if ($event->engage && ! $card->Engaged)
                        $card->Engaged = true;

                    $card->IsUpdated = true;
                    if ($card instanceof Character) 
                    {
                        foreach ($card->Attachments as $attachmentId) {
                            $attachment = $theah->getAttachmentById($attachmentId);
                            if ($attachment == null)
                            {
                                continue;
                            }

                            $deck->moveCard($attachmentId, $event->toLocation, $attachment->ControllerId);
                            $attachment->Location = $event->toLocation;
                            $attachment->IsUpdated = true;
                        }
                    }

                    $message = clienttranslate('${card_inject_code} moved from ${fromLocation} to ${toLocation}');
                    if ($event->sourceId != 0)
                    {
                        $source = $theah->getCardById($event->sourceId);
                        $message .= clienttranslate(' due to: ${source_inject_code}.');
                        $sourceCode = $source->getInjectCode();
                    }
                    else
                    {
                        $message .= clienttranslate('.');
                        $sourceCode = "";
                    }

                    $theah->game->notify->all("cardMoved", $message, [
                        'i18n' => ['fromLocation', 'toLocation'],
                        "card_inject_code" => $card->getInjectCode(),
                        "cardId" => $card->Id,
                        "fromLocation" => $event->fromLocation,
                        "toLocation" => $event->toLocation,
                        "engage" => $card->Engaged,
                        "source_inject_code" => $sourceCode,
                    ]);
                };
                $handler($this, $event);    
                break;

            case $event instanceof EventCardRemovedFromCityDiscardPile:
                $handler = function (Theah $theah, EventCardRemovedFromCityDiscardPile $event)
                {
                    $card = $theah->getCardById($event->cardId);

                    $theah->game->notify->all("cardRemovedFromCityDiscardPile", clienttranslate('${card_name} removed from City Discard pile.'), [
                        'i18n' => ['card_name'],
                        "card_name" => $card->Name,
                        "card" => $card->getPropertyArray($theah->game),
                    ]);    
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardRemovedFromLocker:
                $handler = function (Theah $theah, EventCardRemovedFromLocker $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $theah->game->notify->all("cardRemovedFromLocker", clienttranslate('${card_inject_code} has been removed from ${player_name}\'s locker.'), [
                        "card_inject_code" => $card->getInjectCode(),
                        "playerId" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "cardId" => $card->Id
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardRemovedFromPlay:
                $handler = function (Theah $theah, EventCardRemovedFromPlay $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $card->Location = $event->toLocation;
                    $card->IsUpdated = true;

                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($card->Id, $event->toLocation, $card->ControllerId);

                    $message = clienttranslate('${card_inject_code} removed from play.');
                    if ($event->hidden)
                        $message = clienttranslate('[Hidden Card] removed from play.');

                    $theah->game->notify->all("cardRemovedFromPlay", $message, [
                        "card_inject_code" => $card->getInjectCode(),
                        "cardId" => $card->Id,
                        "toLocation" => $event->toLocation,
                    ]);
                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventCardRemovedFromPlayerDiscardPile:
                $handler = function (Theah $theah, EventCardRemovedFromPlayerDiscardPile $event)
                {
                    if ($event->messageHidden)
                    {
                        $message = clienttranslate('[Hidden Card] removed from ${player_name}\'s discard pile.');
                    }
                    else
                    {
                        $message = clienttranslate('${card_inject_code} removed from ${player_name}\'s discard pile.');
                    }
                    $card = $theah->getCardById($event->cardId);
                    $theah->game->notify->all("cardRemovedFromPlayerDiscardPile", $message, [
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
                        "card" => $card->getPropertyArray($this->game),
                    ]);

                    if ($event->permanentlyHide)
                    {
                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->cardId, Game::LOCATION_PERMANENTLY_HIDDEN);
                    }
                };
                $handler($this, $event);
                break;

                case $event instanceof EventCharacterCombatModified:
                    $handler = function (Theah $theah, EventCharacterCombatModified $event)
                    {
                        $character = $theah->getCharacterById($event->CharacterId);
                        $character->ModifiedCombat = max(0, $event->NewCombat);
                        $character->IsUpdated = true;

                        $theah->game->notify->all("characterCombatModified", clienttranslate('The combat of ${character_name} went from ${oldCombat} to ${newCombat} due to: ${reason}.'), [
                            'i18n' => ['character_name'],
                            "character_name" => $character->Name,
                            "characterId" => $character->Id,
                            "oldCombat" => $event->OldCombat, 
                            "newCombat" => $event->NewCombat,
                            "reason" => $event->Reason,
                        ]);
                    };
                    $handler($this, $event);
                    break;

                case $event instanceof EventCharacterFinesseModifed:
                    $handler = function (Theah $theah, EventCharacterFinesseModifed $event)
                    {
                        $character = $theah->getCharacterById($event->CharacterId);
                        $character->ModifiedFinesse = max(0, $event->NewFinesse);
                        $character->IsUpdated = true;
    
                        $theah->game->notify->all("characterFinesseModifed", clienttranslate('The finesse of ${character_name} went from ${oldFinesse} to ${newFinesse} due to: ${reason}.'), [
                            'i18n' => ['character_name'],
                            "character_name" => $character->Name,
                            "characterId" => $character->Id,
                            "oldFinesse" => $event->OldFinesse, 
                            "newFinesse" => $event->NewFinesse,
                            "reason" => $event->Reason,
                        ]);
                    };
                    $handler($this, $event);
                    break;
    
                case $event instanceof EventCharacterInfluenceModified:
                    $handler = function (Theah $theah, EventCharacterInfluenceModified $event)
                    {
                        $character = $theah->getCharacterById($event->CharacterId);
                        if ($character->DashedInfluence)
                        {
                            return;
                        }
                    
                        $character->ModifiedInfluence = max(0, $event->NewInfluence);
                        $character->IsUpdated = true;

                        $theah->game->notify->all("characterInfluenceModified", clienttranslate('The influence of ${character_inject_code} went from ${oldInfluence} to ${newInfluence} due to: ${reason}.'), [
                            "character_inject_code" => $character->getInjectCode(),
                            "characterId" => $character->Id,
                            "oldInfluence" => $event->OldInfluence, 
                            "newInfluence" => $event->NewInfluence,
                            "reason" => $event->Reason,
                        ]);
                    };
                    $handler($this, $event);
                    break;

                case $event instanceof EventCardMustered:
                    $handler = function (Theah $theah, EventCardMustered $event)
                    {
                        //Update the character's location in the DB
                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->cardId, $event->location, $event->playerId);

                        $card = $theah->getCardById($event->cardId);
                        $card->Location = $event->location;
                        $card->ControllerId = $event->playerId;
                        $card->IsUpdated = true;
                        $theah->addCardToWorld($card);

                        // Notify players of mustered character
                        $theah->game->notify->all("cardMustered", clienttranslate('${player_name} plays ${card_inject_code} at ${location}.'), [
                            'i18n' => ['location'],
                            "player_id" => $event->playerId,
                            "player_name" => $theah->game->getPlayerNameById($event->playerId),
                            "card_inject_code" => $card->FaceDown ? clienttranslate('[Hidden]') : $card->getInjectCode(),
                            "location" => $event->location,
                            "card" => $card->getPropertyArray($theah->game),
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
                        $theah->addCardToWorld($character);        

                        $message = clienttranslate('${player_name} musters ${character_inject_code} at ${location}.');
                        if ($event->location != Game::LOCATION_PLAYER_HOME)
                            $message = clienttranslate('${player_name} plays ${character_inject_code} at ${location}.');
        
                        // Notify players of mustered character
                        $theah->game->notify->all("cardMustered", $message, [
                            'i18n' => ['location'],
                            "player_id" => $event->playerId,
                            "player_name" => $theah->game->getPlayerNameById($event->playerId),
                            "character_inject_code" => $character->getInjectCode(),
                            "location" => $event->location,
                            "card" => $character->getPropertyArray($theah->game),
                        ]);

                        if ($character instanceof Brute)
                        {
                            $deck = $theah->game->getGameDeckObject();
                            $theah->game->notify->all("cardRemovedFromHand", '', [
                                "playerId" => $event->playerId,
                                "cardId" => $event->characterId,
                                'handCount' => count($deck->getPlayerHand($event->playerId)),
                            ]);
                        }
                    };
                    $handler($this, $event);
                    break;

            case $event instanceof EventCharacterRecruited:
                $character = $this->cards[$event->characterId];
                $character->ControllerId = $event->playerId;
                $character->IsUpdated = true;

                // Notify players of recruited character
                $message = clienttranslate('${player_name} recruits ${character_inject_code} at a cost of ${cost} Wealth.');
                if ($event->discount > 0)
                {
                    $message .= clienttranslate(' (with a discount of ${discount}).');
                    if ($event->explanations != '')
                    {
                        $message .= clienttranslate('<br>${explanations}');
                    }
                }
                $this->game->notify->all("characterRecruited", $message, [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_inject_code" => $character->getInjectCode(),
                    "characterId" => $character->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
                    "explanations" => $event->explanations,
                ]);
                break;

            case $event instanceof EventCityCardAddedToLocation:
                $handler = function (Theah $theah, EventCityCardAddedToLocation $event)
                {
                    $card = $theah->game->getCardObjectFromDb($event->cardId);
                    $card->Location = $event->location;
                    $card->IsUpdated = true;
                    $theah->addCardToWorld($card);

                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, $event->location);
                    
                    // Notify players that card has been played
                    $theah->game->notify->all("cityCardAddedToLocation", clienttranslate('${card_inject_code} added to ${location}.'), [
                        'i18n' => ['location'],
                        "card_inject_code" => $card->getInjectCode(),
                        "location" => $event->location,
                        "card" => $card->getPropertyArray($theah->game)
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCombatCardAnnounced:
                $handler = function (Theah $theah, EventCombatCardAnnounced $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $theah->game->notify->all("message", clienttranslate('${player_name} announces ${card_inject_code} as their Combat Card.'), [
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationBecomesUncontrolled:
                $handler = function (Theah $theah, EventLocationBecomesUncontrolled $event)
                {
                    $location = $theah->getCityLocation($event->location);
                    $theah->game->setControllerForLocation($location->Name, 0);
                    $location->Controller = 0;
        
                    $theah->game->notify->all("locationUncontrolled", clienttranslate('${location_name} is now uncontrolled.'), [
                        "i18n" => ["location_name"],
                        "location_name" => $location->Name,
                        "location" => $location->Name,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationClaimed:
                $handler = function (Theah $theah, EventLocationClaimed $event)
                {
                    $theah->game->setControllerForLocation($event->location, $event->playerId);
                    $this->cityLocations[$event->location]->Controller = $event->playerId;

                    $theah->game->notify->all("locationClaimed", clienttranslate('${player_name} Claimed <strong>${location_name}</strong>.'), [
                        'i18n' => ['card_name', 'location_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "location_name" => $event->location,
                        "playerId" => $event->playerId,
                        "location" => $event->location,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPressureOccuring:
                $handler = function (Theah $theah, EventPressureOccuring $event)
                {
                    $pressureTypes = implode(", ", $event->pressureTypes);
                    if ($event->performerId != 0)
                    {
                        $performer = $theah->getCharacterById($event->performerId);
                        $theah->game->notify->all("message", clienttranslate('${player_name} is Pressuring ${location} with ${performer_inject_code} using ${pressureTypes}.'), [
                            'i18n' => ['location'],
                            "player_name" => $this->game->getPlayerNameById($event->playerId),
                            "performer_inject_code" => $performer->getInjectCode(),
                            "location" => $event->location,
                            "pressureTypes" => $pressureTypes,
                        ]);
                    }
                    else
                    {
                        $theah->game->notify->all("message", clienttranslate('${player_name} is Pressuring ${location} using ${pressureTypes}.'), [
                            'i18n' => ['location'],
                            "player_name" => $this->game->getPlayerNameById($event->playerId),
                            "location" => $event->location,
                            "pressureTypes" => $pressureTypes,
                        ]);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationPressured:
                $handler = function (Theah $theah, EventLocationPressured $event)
                {
                    $performer = $event->performerId ? $theah->getCharacterById($event->performerId) : null;
                    $performerInjectCode = $performer ? $performer->getInjectCode() : "";
                    $theah->game->notify->all("message", clienttranslate('${player_name} chose ${performer_inject_code} to Pressure ${location}.
                    <br>Pressure Type: ${pressureType}
                    <br>Influence Totals: ${totals}'), [
                        'i18n' => ['location', 'pressureType'],
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "performer_inject_code" => $performerInjectCode,
                        "location" => $event->location,
                        "pressureType" => $event->pressureType,
                        "totals" => $event->totalsExplanation,
                    ]);

                    $pressureResultEvent = EventFactory::createLocationPressureResultEvent(
                        $event->playerId, 
                        $event->performerId, 
                        $event->location, 
                        $event->pressureType, 
                        $event->success, 
                        $event->totalsExplanation, 
                        $event->highDramaBasicAction, 
                        $event->abilityId);
                    $theah->queueEvent($pressureResultEvent);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventLocationPressureResult:
                $handler = function (Theah $theah, EventLocationPressureResult $event)
                {
                    $theah->game->notify->all("message", clienttranslate('Pressure Result: ${result}.'), [
                        "result" => $event->success ? clienttranslate("SUCCESS") : clienttranslate("FAILED"),
                    ]);

                    if ($event->highDramaBasicAction)
                    {
                        if ($event->success)
                        {
                            $claimEvent = EventFactory::createLocationClaimedEvent($event->playerId, $event->performerId, $event->location);
                            $theah->eventCheck($claimEvent);
                            $theah->queueEvent($claimEvent);
                        }

                        $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
                        $theah->queueEvent($actionResolvedEvent);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventManeuverUsed:
                $handler = function (Theah $theah, EventManeuverUsed $event)
                {
                    $theah->game->notify->all('maneuverUsed', '', [
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
                    $theah->game->notify->all("reknownUpdatedOnCard", clienttranslate('${player_name} added ${amount} Renown to ${card_name} (${total} now on card).'), [
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
                    $theah->game->notify->all("reknownUpdatedOnCard", clienttranslate('${player_name} removed ${amount} Renown from ${card_name} (${total} now on card).'), [
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
                        $this->game->notify->all("playerReknownUpdated", clienttranslate('${player_name} loses ${amount} Renown (now at ${total}).'), [
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
                    $this->game->notify->all("playerReknownUpdated", clienttranslate('${player_name} gains ${amount} Renown (now at ${total}).'), [
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
                    $theah->game->notify->all("reactionUsed", '', [
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
                $reknown = $this->game->getRenownForLocation($event->location) + $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

                $this->cityLocations[$event->location]->Renown += $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notify->all("reknownAddedToLocation", clienttranslate('${amount} Renown ADDED to ${location} ${source}.'), [
                    'i18n' => ['location'],
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->description) ? "" : "from {$event->description}",
                ]);

                break;

            case $event instanceof EventReknownRemovedFromLocation:

                //Update the reknown for the location in the database
                $reknown = $this->game->getRenownForLocation($event->location) - $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

                $this->cityLocations[$event->location]->Renown -= $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notify->all("reknownRemovedFromLocation", clienttranslate('${amount} Renown REMOVED from ${location} ${source}.'), [
                    'i18n' => ['location'],
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->source) ? "" : "from {$event->source}",
                ]);

                break;

            case $event instanceof EventSchemeCardRevealed:
                $scheme = $this->getSchemeById($event->schemeId);
                $this->cards[$event->schemeId] = $scheme;

                $scheme->Location = $event->location;
                $scheme->IsUpdated = true;

                // Notify players of selected scheme
                $this->game->notify->all("approachSchemePlayed", clienttranslate('${player_name} plays ${scheme_inject_code} as their Approach Scheme.'), [
                    "player_name" => $event->playerName,
                    "scheme_inject_code" => $scheme->getInjectCode(),
                    "player_id" => $event->playerId,
                    "scheme" => $scheme->getPropertyArray($this->game),
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
                    $theah->game->notify->all("techniqueUsed", '', [
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
                    
                    $message = clienttranslate('${player_name} has chosen ${challenger_inject_code} to Challenge ${defender_inject_code}. ');

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
                    $techniqueOwner = null;
                    if ($event->activatedTechniqueId)
                    {
                        $message .= clienttranslate('<br>${player_name} will activate Technique [${technique_name}] from ${technique_inject_code} for the Challenge.');
                        $technique = $theah->getTechniqueById($event->activatedTechniqueId);
                        $techniqueOwner = $technique->getOwningCard($theah);
                    }
                    else
                    {
                        $message .= clienttranslate('<br>No Technique will be activated for the Challenge.');
                    }
                                            
                    $theah->game->notify->all("challengeIssued", $message, [
                        'i18n' => ['technique_name'],
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "challenger_inject_code" => $challenger->getInjectCode(),
                        "defender_inject_code" => $defender->getInjectCode(),
                        "technique_name" => $technique?->Name,
                        "technique_inject_code" => $techniqueOwner?->getInjectCode(),
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

                    $theah->game->notify->all("challengerSwapped", clienttranslate('${player_name} has swapped ${challenger_inject_code} for ${new_challenger_inject_code}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "challenger_inject_code" => $challenger->getInjectCode(),
                        "new_challenger_inject_code" => $newChallenger->getInjectCode(),
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
                    $theah->addCardToWorld($character);

                    $theah->game->notify->all("message", clienttranslate('${character_inject_code} has been put into ${player_name}\'s Approach Deck.'), [
                        "character_inject_code" => $character->getInjectCode(),
                        "player_name" => $theah->game->getPlayerNameById($event->playerId)
                    ]);

                    $theah->game->notify->player($event->playerId, "approachCardsReceived", 
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
                    $theah->game->notify->all("characterIntervened", clienttranslate('${player_name} has chosen to have ${intervener_inject_code} INTERVENE in the Challenge in place of ${target_inject_code}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "intervener_inject_code" => $newTarget->getInjectCode(),
                        "target_inject_code" => $oldTarget->getInjectCode(),
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

                    $theah->game->notify->all("defenderSwapped", clienttranslate('${player_name} has swapped ${defender_inject_code} for ${new_defender_inject_code}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "defender_inject_code" => $defender->getInjectCode(),
                        "new_defender_inject_code" => $newDefender->getInjectCode(),
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
                        $theah->game->notify->all("message", $theah->game->translate($explanation));
                    }
                    
                    $theah->game->globals->set(Game::CHALLENGER_THREAT, $event->actorThreat);
                    $theah->game->globals->set(Game::DEFENDER_THREAT, $event->adversaryThreat);
                    $theah->game->globals->set(Game::DEFENDER_THREAT_IS_LETHAL, $event->adversaryThreatIsLethal);

                    $actor = $theah->cards[$event->actorId];
                    $adversary = $theah->cards[$event->adversaryId];

                    $message = clienttranslate('${actor_name} has ${actor_threat} total Threat for the Challenge. ${adversary_name} has ${adversary_threat} total Threat. ');
                    if ($event->adversaryThreatIsLethal)
                    {
                        $message .= clienttranslate('${adversary_name} has LETHAL Threat.');
                    }

                    $theah->game->notify->all("message", $message, [
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
                    if (!$event->copied)
                        $technique->setUsed($theah, true);

                    $theah->game->notify->all("techniqueActivated", clienttranslate('${player_name} has activated the Technique [${technique_name}].'), [
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
                    if ($event->inDuel)
                    {
                        $duelId = $theah->game->globals->get(Game::DUEL_ID);
                        $round = $theah->game->globals->get(Game::DUEL_ROUND);
                        $owningCard = $technique->getOwningCard($theah);
                        $name = substr(addslashes($owningCard->Name) . ": " . addslashes($technique->Name), 0, 500);
                        $isMain = $theah->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN, false) ? 1 : 0;
                        $sql = "INSERT INTO duel_round_technique (duel_id, round, technique_id, technique_name, technique_is_main) VALUES ($duelId, $round, '{$event->techniqueId}', '$name', $isMain)";
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

                    //Get the combat cards played in this round to determine if any are dashed
                    $dashedRiposte = true;
                    $dashedParry = true;
                    $dashedThrust = true;
                    $sql = "SELECT combat_card_id FROM duel_round_combat_card where duel_id = $duelId AND round = {$round}";
                    $db = $theah->getDBObject();
                    $combatCardIds = $db->getCollection($sql);
                    if (count($combatCardIds) == 0)
                    {
                        $dashedRiposte = false;
                        $dashedParry = false;
                        $dashedThrust = false;
                    }
                    
                    foreach ($combatCardIds as $combatCardId)
                    {
                        $combatCard = $theah->getCardById($combatCardId['combat_card_id']);
                        if (!$combatCard->DashedRiposte)
                            $dashedRiposte = false;
                        if (!$combatCard->DashedParry)
                            $dashedParry = false;
                        if (!$combatCard->DashedThrust)
                            $dashedThrust = false;
                    }

                    if ($dashedRiposte && $event->riposte > 0)
                    {
                        $event->riposte = 0;
                        $event->explanations[] = $theah->game->translate("Combat Card(s) Riposte is dashed so Technique Riposte will not be applied.");
                    }
                    if ($dashedParry && $event->parry > 0)
                    {
                        $event->parry = 0;
                        $event->explanations[] = $theah->game->translate("Combat Card(s) Parry is dashed so Technique Parry will not be applied.");
                    }
                    if ($dashedThrust && $event->thrust > 0)
                    {
                        $event->thrust = 0;
                        $event->explanations[] = $theah->game->translate("Combat Card(s) Thrust is dashed so Technique Thrust will not be applied.");
                    }

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notify->all("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "technique", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";

                    $riposteText = $theah->game->translate("Riposte");
                    $parryText = $theah->game->translate("Parry");
                    $thrustText = $theah->game->translate("Thrust");
                    if ($results["riposte"] > 0) 
                    {
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    if ($results["parry"] > 0) 
                    {
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    if ($results["thrust"] > 0) 
                    {
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }

                    if ($results["riposte"] < 0) 
                    {
                        $effects .= "<p>{$riposteText} {$results["riposte"]}";
                    }
                    if ($results["parry"] < 0) 
                    {
                        $effects .= "<p>{$parryText} {$results["parry"]}";
                    }
                    if ($results["thrust"] < 0) 
                    {
                        $effects .= "<p>{$thrustText} {$results["thrust"]}";
                    }

                    $challengerId = $theah->getDuelChallengerId();
                    $challeger = $theah->getCharacterById($challengerId);
                    $defenderId = $theah->getDuelDefenderId();
                    $defender = $theah->getCharacterById($defenderId);

                    $challengerThreatIsLethal = $results['challengerThreatIsLethal'];
                    $defenderThreatIsLethal = $results['defenderThreatIsLethal'];

                    $challengerThreatIsLethalText = $challengerThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $challeger->getInjectCode()) : "";
                    $defenderThreatIsLethalText = $defenderThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $defender->getInjectCode()) : "";

                    $effects .= "<p>At the end of the round, the following will occur:</p>";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"], $challengerThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $challengerThreatIsLethalText);

                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"], $defenderThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $defenderThreatIsLethalText);

                    $deck = $theah->game->getGameDeckObject();
                    $theah->game->notify->all("updateRoundWithCombatStats", clienttranslate('Duel Update: ${character_inject_code} adds the Technique [<strong>${effect_name}</strong>] from ${card_inject_code}. ${effects}'), [
                        'i18n' => ['character_name', 'effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "technique",
                        "character_inject_code" => $actor->getInjectCode(),
                        "card_inject_code" => $owningCard->getInjectCode(),
                        "cardName" => $owningCard->Name,
                        "effect_name" => $technique->Name,
                        "effectName" => $technique->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "challengerThreatIsLethal"  => $results["challengerThreatIsLethal"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                        "defenderThreatIsLethal"  => $results["defenderThreatIsLethal"],
                        "wounds" => $results["wounds"],
                        "handCount" => count($deck->getPlayerHand($actor->ControllerId)),
                    ]);                    
                };    
                $handler($this, $event);
                break;

            case $event instanceof EventManeuverActivated:
                $handler = function ($theah, EventManeuverActivated $event)
                {
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    $maneuver->setUsed($theah, true);

                    $theah->game->notify->all("maneuverActivated", clienttranslate('${player_name} has activated the Maneuver [${maneuver_name}].'), [
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
                    $name = substr(addslashes($owningCard->Name) . ": " . addslashes($maneuver->Name), 0, 500);
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
                        $theah->game->notify->all("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "maneuver", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";
                    $riposteText = $theah->game->translate("Riposte");
                    $parryText = $theah->game->translate("Parry");
                    $thrustText = $theah->game->translate("Thrust");
                    if ($results["riposte"] > 0) 
                    {
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    if ($results["parry"] > 0) 
                    {
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    if ($results["thrust"] > 0) 
                    {
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }

                    if ($results["riposte"] < 0) 
                    {
                        $effects .= "<p>{$riposteText} {$results["riposte"]}";
                    }
                    if ($results["parry"] < 0) 
                    {
                        $effects .= "<p>{$parryText} {$results["parry"]}";
                    }
                    if ($results["thrust"] < 0) 
                    {
                        $effects .= "<p>{$thrustText} {$results["thrust"]}";
                    }

                    $challengerId = $theah->getDuelChallengerId();
                    $challeger = $theah->getCharacterById($challengerId);
                    $defenderId = $theah->getDuelDefenderId();
                    $defender = $theah->getCharacterById($defenderId);

                    $challengerThreatIsLethal = $results['challengerThreatIsLethal'];
                    $defenderThreatIsLethal = $results['defenderThreatIsLethal'];

                    $challengerThreatIsLethalText = $challengerThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $challeger->getInjectCode()) : "";
                    $defenderThreatIsLethalText = $defenderThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $defender->getInjectCode()) : "";

                    $effects .= "<p>At the end of the round, the following will occur:</p>";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"], $challengerThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $challengerThreatIsLethalText);

                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"], $defenderThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $defenderThreatIsLethalText);

                    $message = clienttranslate('Duel Update: ${character_inject_code} activated the Maneuver [${effect_name}] ${effects}');
                    if (! $maneuverCard instanceof Character)
                    {
                        $message = clienttranslate('Duel Update: ${character_inject_code} has activated the Maneuver [${effect_name}] from ${card_inject_code} ${effects}');
                    }

                    $deck = $theah->game->getGameDeckObject();
                    $theah->game->notify->all("updateRoundWithCombatStats", $message, [
                        'i18n' => ['character_name', 'effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "maneuver",
                        "character_inject_code" => $actor->getInjectCode(),
                        "card_inject_code" => $maneuverCard->getInjectCode(),
                        "effect_name" => $maneuver->Name,
                        "effectName" => $maneuver->Name,
                        "cardName" => $maneuverCard->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                        "challengerThreatIsLethal"  => $results["challengerThreatIsLethal"],
                        "defenderThreatIsLethal"  => $results["defenderThreatIsLethal"],
                        "wounds" => $results["wounds"],
                        "handCount" => count($deck->getPlayerHand($actor->ControllerId)),
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
                        $theah->game->notify->all("message", $theah->game->translate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "combat", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";  
                    $riposteText = $theah->game->translate("Riposte");
                    $parryText = $theah->game->translate("Parry");
                    $thrustText = $theah->game->translate("Thrust");
                    if ($results["riposte"] > 0) 
                    {
                        $effects .= "<p>{$riposteText} +{$results["riposte"]}";
                    }
                    if ($results["parry"] > 0) 
                    {
                        $effects .= "<p>{$parryText} +{$results["parry"]}";
                    }
                    if ($results["thrust"] > 0) 
                    {
                        $effects .= "<p>{$thrustText} +{$results["thrust"]}";
                    }

                    if ($results["riposte"] < 0) 
                    {
                        $effects .= "<p>{$riposteText} {$results["riposte"]}";
                    }
                    if ($results["parry"] < 0) 
                    {
                        $effects .= "<p>{$parryText} {$results["parry"]}";
                    }
                    if ($results["thrust"] < 0)
                    {
                        $effects .= "<p>{$thrustText} {$results["thrust"]}";
                    }

                    $challengerId = $theah->getDuelChallengerId();
                    $challeger = $theah->getCharacterById($challengerId);
                    $defenderId = $theah->getDuelDefenderId();
                    $defender = $theah->getCharacterById($defenderId);

                    $challengerThreatIsLethal = $results['challengerThreatIsLethal'];
                    $defenderThreatIsLethal = $results['defenderThreatIsLethal'];

                    $challengerThreatIsLethalText = $challengerThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $challeger->getInjectCode()) : "";
                    $defenderThreatIsLethalText = $defenderThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $defender->getInjectCode()) : "";
    
                    $effects .= "<p>At the end of the round, the following will occur:</p>";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $results["endingChallengerThreatAfter"], $challengerThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $challeger->getInjectCode(), $results["endingChallengerThreatBefore"], $challengerThreatIsLethalText);

                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will go from %d to %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $results["endingDefenderThreatAfter"], $defenderThreatIsLethalText);
                    else
                        $effects .= sprintf($theah->game->translate("<p>%s Threat will remain at %d. %s"), $defender->getInjectCode(), $results["endingDefenderThreatBefore"], $defenderThreatIsLethalText);

                    $deck = $theah->game->getGameDeckObject();
                    $theah->game->notify->all("updateRoundWithCombatStats", clienttranslate('Duel Update: ${player_name} has played ${card_inject_code} as their Combat Card. ${effects}'), [
                        'i18n' => ['effect_name', 'effects'],
                        "round" => $round,
                        "mode" => "combat",
                        "player_name" => $playerName,
                        "playerId" => $playerId,
                        "card_inject_code" => $card->getInjectCode(),
                        "effectName" => $card->Name,
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
                        "challengerThreatIsLethal"  => $results["challengerThreatIsLethal"],
                        "defenderThreatIsLethal"  => $results["defenderThreatIsLethal"],
                        "wounds" => $results["wounds"],
                        "handCount" => count($deck->getPlayerHand($playerId)),
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
                    $theah->game->notify->all("newDuelRound", clienttranslate('DUEL ROUND #${round} HAS STARTED for ${player_name} and their ${role} ${character_inject_code}.'), [
                        'i18n' => ['role'],
                        "player_name" => $playerName,
                        "role" => $event->round % 2 == 1 ? "Defending Character" : "Challenging Character",
                        "character_inject_code" => $actor->getInjectCode(),
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
                        "challengerThreatIsLethal" => $event->challengerThreatIsLethal,
                        "defenderThreatIsLethal" => $event->defenderThreatIsLethal,
                        "wounds" => $event->wounds
                    ]);            
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelGambleCardsRevealed:
                break;

            case $event instanceof EventDuelPlayerGambled:
                $handler = function(Theah $theah, EventDuelPlayerGambled $event) {
                    $card = $theah->game->getCardObjectFromDb($event->chosenCardId);
                    $theah->addCardToWorld($card);

                    $message = clienttranslate('${player_name} has gambled with ${card_inject_code}. ${count} cards were revealed.');
                    if ($event->explanations != '')
                    {
                        $message .= clienttranslate('<br>${explanations}');
                    }

                    $theah->game->notify->all("message", $message, [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_inject_code" => $card->getInjectCode(),
                        "count" => $event->revealCount,
                        "explanations" => $event->explanations,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelActionsDone:
                $handler = function (Theah $theah, EventDuelActionsDone $event)
                {
                    $theah->game->notify->all("message", clienttranslate('${player_name} is done with their actions for the round.'), [
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

                    $theah->game->notify->all("duelEnd", clienttranslate('The Duel has ended.'), [
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

                    $theah->game->notify->all("challengeRejected", clienttranslate('${player_name} REFUSES The Challenge.'), [
                        "player_name" => $theah->game->getPlayerNameById($defender->ControllerId),
                        "challengerId" => $event->challengerId,
                        "defenderId" => $event->targetId,
                    ]);                       
                };
                $handler($this, $event);
                break;

                case $event instanceof EventCharacterBeingHealed:
                    $handler = function ($theah, EventCharacterBeingHealed $event)
                    {
                        $healedEvent = self::createEvent(Events::CharacterHealed);
                        if ($healedEvent instanceof EventCharacterHealed)
                        {
                            $healedEvent->characterId = $event->characterId;
                            $healedEvent->sourceId = $event->sourceId;
                            $healedEvent->wounds = $event->wounds;
                            $healedEvent->reason = $event->reason;
                            $healedEvent->abilityId = $event->abilityId;
                        }
                        $event->theah->queueEvent($healedEvent);
                    };
                    $handler($this, $event);
                    break;

            case $event instanceof EventCharacterBeingWounded:
                $handler = function ($theah, EventCharacterBeingWounded $event)
                {
                    $woundedEvent = self::createEvent(Events::CharacterWounded);
                    if ($woundedEvent instanceof EventCharacterWounded)
                    {
                        $woundedEvent->characterId = $event->characterId;
                        $woundedEvent->sourceId = $event->sourceId;
                        $woundedEvent->wounds = $event->wounds;
                        $woundedEvent->reason = $event->reason;
                        $woundedEvent->abilityId = $event->abilityId;
                    }
                    $event->theah->queueEvent($woundedEvent);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCharacterWounded:
                $handler = function ($theah, EventCharacterWounded $event)
                {
                    $inDuel = $theah->game->globals->get(Game::IN_DUEL);
                    if ($inDuel)
                    {
                        $character = $theah->getCardById($event->characterId);
                        $actor = $theah->getDuelRoundActor();
                        if ($character->Id == $actor->Id)
                        {
                            $serialized = addslashes(serialize($character));
                            $duelId = $theah->game->globals->get(Game::DUEL_ID);
                            $round = $theah->game->globals->get(Game::DUEL_ROUND);
                            $sql = "UPDATE duel_round set actor_serialized = '$serialized' where duel_id = $duelId and round = $round";
                            $theah->game->DbQuery($sql);
                        }
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCharacterDestroyed:
                $handler = function ($theah, EventCharacterDestroyed $event)
                {
                    $character = $theah->getCardById($event->characterId);
                    $locker = $theah->game->getPlayerLockerName($character->ControllerId);
                    $location = $locker;

                    if ($character instanceof Brute)
                    {
                        $discardPileName = $theah->game->getPlayerDiscardDeckName($character->ControllerId);
                        $location = $discardPileName;
                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->characterId, $discardPileName);

                        $theah->game->notify->all("cardDiscardedFromPlay", clienttranslate('${card_inject_code} has been destroyed and sent to the discard pile due to: ${reason} '), [
                            'i18n' => ['location'],
                            "playerId" => $character->ControllerId,
                            "card_inject_code" => $character->getInjectCode(),
                            "cardId" => $character->Id,
                            "location" => $character->Location,
                            "reason" => $event->reason,
                        ]);
                        }
                    else
                    {
                        //Agent006: "Mercenary characters granted Brute by Cirilo's Passive would not have the Brute keyword upon destruction and leaving play and would be sent to the 'The Locker'"
                        $deck = $theah->game->getGameDeckObject();
                        $deck->moveCard($event->characterId, $locker);

                        $theah->game->notify->all("characterDestroyed", clienttranslate('${target_inject_code} has been destroyed and sent to the locker due to: ${reason} '), [
                            'i18n' => ['reason'],
                            "playerId" => $event->playerId,
                            "target_inject_code" => $character->getInjectCode(),
                            "characterId" => $event->characterId,
                            "reason" => $event->reason,
                        ]);
                    }

                    //Card has been destroyed, so recreate it because it has no memory of past state
                    $fullClassname = get_class($character);
                    $pos = strrpos($fullClassname, '\\');
                    $className = substr($fullClassname, $pos + 2);
                    $character = $theah->game->instantiateCard($className, $event->characterId);            
                    $character->Location = $location;
                    $character->IsUpdated = true;
                    $theah->addCardToWorld($character);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCardSentToLocker:
                $handler = function ($theah, EventCardSentToLocker $event)
                {
                    $card = $theah->getCardById($event->cardId);
                    $locker = $theah->game->getPlayerLockerName($event->playerId);
                    $deck = $theah->game->getGameDeckObject();
                    $deck->moveCard($event->cardId, $locker);

                    $card->Location = $locker;
                    $card->IsUpdated = true;

                    $theah->game->notify->all("cardSentToLocker", clienttranslate('${card_inject_code} has been sent to the locker.'), [
                        "playerId" => $event->playerId,
                        "card_inject_code" => $card->getInjectCode(),
                        "card" => $card->getPropertyArray($theah->game),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventEnteringPayState:
                $handler = function ($theah, EventEnteringPayState $event)
                {
                    $payDiscountEvent = EventFactory::createCalculatePayDiscountEvent($event->playerId, $event->cardId, $event->payStateType, $event->internalId);
                    $payDiscountEvent->priority = $event->priority;
                    if ($event->wasStacked ?? false)
                    {
                        $theah->stackEvent($payDiscountEvent);
                    }
                    else
                    {
                        $theah->queueEvent($payDiscountEvent);
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventCalculatePayDiscount:
                $handler = function ($theah, EventCalculatePayDiscount $event)
                {
                    [$discount, $explanations] = $theah->calculateInHandPayDiscount($event->playerId, $event->payStateType, $event->cardId, $event->internalId);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventHighDramaPhasePlayerPassed:
                $handler = function ($theah, EventHighDramaPhasePlayerPassed $event)
                {
                    // Notify all players about the choice to pass.
                    $theah->game->notify->all("message", clienttranslate('${player_name} has passed for their High Drama Action.'), [
                        "player_id" => $event->playerId,
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventHighDramaPhaseEnd:
                $handler = function ($theah, EventHighDramaPhaseEnd $event)
                {
                    $theah->game->notify->all("highDramaPhaseEnd", clienttranslate('<strong>END OF HIGH DRAMA PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlunderPhaseBegin:
                $handler = function ($theah, EventPlunderPhaseBegin $event)
                {
                    $theah->game->notify->all("plunderPhaseBegin", clienttranslate('<strong>BEGINNING OF PLUNDER PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventPlayerTakeReknownForControlledLocation:
                $handler = function ($theah, EventPlayerTakeReknownForControlledLocation $event)
                {
                    $theah->game->notify->all("message", clienttranslate('${player_name} controls ${location_name} and will receive ${reknown} Renown.'), [
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
                    $theah->game->notify->all("plunderPhaseEnd", clienttranslate('<strong>END OF PLUNDER PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventThreatModified:
                $handler = function ($theah, EventThreatModified $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $challengerId = $theah->getDuelChallengerId();
                    $defenderId = $theah->getDuelDefenderId();
                    $challenger = $theah->getCardById($challengerId);
                    $defender = $theah->getCardById($defenderId);

                    $db = $theah->getDBObject();
                    $db->updateRoundThreats($duelId, $round, $event->challengerThreat, $event->defenderThreat, $event->challengerThreatIsLethal, $event->defenderThreatIsLethal);

                    $result = $db->getRoundThreats($duelId, $round);
                    $endingChallengerThreat = $result['ending_challenger_threat'];
                    $endingDefenderThreat = $result['ending_defender_threat'];
                    $wounds = $result['wounds_taken'];
                    $challengerThreatIsLethal = $result['challenger_threat_is_lethal'];
                    $defenderThreatIsLethal = $result['defender_threat_is_lethal'];

                    $challengerThreatIsLethalText = $challengerThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $challenger->getInjectCode()) : "";
                    $defenderThreatIsLethalText = $defenderThreatIsLethal ? sprintf(clienttranslate("<br><strong>%s Threat is LETHAL</strong>"), $defender->getInjectCode()) : "";

                    $theah->game->notify->all("updateRoundThreats", clienttranslate(
                        'Threat for ${challenger_inject_code} has been modified by ${challenger_modification}.
                        <br>
                        Threat for ${defender_inject_code} has been modified by ${defender_modification}.
                        <br>
                        Current Threat for ${challenger_inject_code}: ${challenger_threat}. ${challenger_lethal_text}
                        <br>
                        Current Threat for ${defender_inject_code}: ${defender_threat}. ${defender_lethal_text}'), [
                        'i18n' => ['challenger_inject_code', 'defender_inject_code'],
                        "challenger_inject_code" => $challenger->getInjectCode(),
                        "defender_inject_code" => $defender->getInjectCode(),
                        "challenger_modification" => $event->challengerThreat,
                        "defender_modification" => $event->defenderThreat,
                        "challenger_threat" => $endingChallengerThreat,
                        "defender_threat" => $endingDefenderThreat,
                        "challenger_lethal_text" => $challengerThreatIsLethalText,
                        "defender_lethal_text" => $defenderThreatIsLethalText,
                        "challengerThreatIsLethal" => $challengerThreatIsLethal,
                        "defenderThreatIsLethal" => $defenderThreatIsLethal,
                        "wounds" => $wounds,
                        "round" => $round,
                    ]);

                };
                $handler($this, $event);
                break;
    
            case $event instanceof EventDuskPhaseBegin:
                $handler = function ($theah, EventDuskPhaseBegin $event)
                {
                    $theah->game->notify->all("duskPhaseBegin", clienttranslate('<strong>BEGINNING OF DUSK PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuskPhaseEnd:
                $handler = function ($theah, EventDuskPhaseEnd $event)
                {
                    $theah->game->notify->all("duskPhaseEnd", clienttranslate('<strong>END OF DUSK PHASE</strong>'), []);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuskEndOfDay:
                $handler = function ($theah, EventDuskEndOfDay $event)
                {
                    $theah->game->notify->all("duskEndOfDay", clienttranslate('<strong>END OF DAY</strong>'), []);
                };
                $handler($this, $event);
                break;
    
        }

    }
}