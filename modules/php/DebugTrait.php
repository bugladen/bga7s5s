<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;

trait DebugTrait
{
    public function debug_AddCardToHand(string $className, int $playerId)
    {
        $card = $this->instantiateCard($className);

        $location = Game::LOCATION_HAND;
        $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', $playerId, '$location', $playerId)";
        $this->DbQuery($sql);

        //Create an instance of the card, set the ID, and save it back into the db
        $id = $this->DbGetLastId();
        $card->setId($id);
        $card->OwnerId = $playerId;
        $card->ControllerId = $playerId;
        $card->Location = $location;
        $this->updateCardObjectInDb($card);

        $this->notifyPlayer($playerId, "drawCard", 'Debug Draw', [
            'i18n' => ['card'],
            "card" => $card->getPropertyArray($this),
        ]);
    }
    
    public function debug_IncludeCityCardInSetup(string $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $this->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, $className);
        }
    }

    public function debug_AddCityCardToTopOfDeck(string $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) 
        {
            $location = Game::LOCATION_CITY_DECK;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', 0, '{$location}', 0)";
            $this->DbQuery($sql);

            //Store the card Id in the object, and serialize the card object to the db
            $id = $this->DbGetLastId();
            $card->setId($id);
            $this->updateCardObjectInDb($card);
            $this->cards->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, true);
        }
    }

    public function debug_SetCardInPlayerDiscardPile(int $playerId, string $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $location = $this->getPlayerDiscardDeckName($playerId);
            $dbCard = $this->cards->getCardsOfType($className);
            $dbCard = reset($dbCard);
            if ($dbCard)
            {
                $this->cards->moveCard($dbCard['id'], $location, $playerId);
                $card = $this->getCardObjectFromDb($dbCard['id']);
                $card->Location = $location;
                $card->ControllerId = $playerId;
                $this->updateCardObjectInDb($card);
            }
        }
    }

    public function debug_SetCardInCityDiscardPile(string $className)
    {
        $card = $this->instantiateCard($className);
        if ($card) {
            $location = Game::LOCATION_CITY_DISCARD;
            $dbCard = $this->cards->getCardsOfType($className);
            $dbCard = reset($dbCard);
            if ($dbCard)
                $this->cards->moveCard($dbCard['id'], $location);
        }
    }

    public function debug_SetCardController(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException(self::_("Card not found"));

        $card->ControllerId = $playerId;
        $this->updateCardObjectInDb($card);
    }

    public function debug_EngageCard(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException(self::_("Card not found"));

        $event = EventFactory::createCardEngagedEvent($playerId, $cardId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_EngardeCard(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException(self::_("Card not found"));

        $event = EventFactory::createCardEngardedEvent($playerId, $cardId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_SetReknownOnCard(int $cardId, int $reknown)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException(self::_("Card not found"));

        $card->Reknown = $reknown;
        $this->updateCardObjectInDb($card);
    }

    public function debug_SetDay(int $day)
    {
        $this->setGameStateValue(Game::DAY, $day);
    }

    public function debug_SetPlayerReknown(int $playerId, int $score)
    {
        $this->DBQuery("UPDATE player SET player_score = $score WHERE player_id = $playerId");
    }

    public function debug_AddReknownToLocation(string $location, int $amount)
    {
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation)
        {
            $event->location = $location;
            $event->amount = $amount;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_RemoveReknownFromLocation(string $location, int $amount)
    {
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation)
        {
            $event->location = $location;
            $event->amount = $amount;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_WoundCharacter(int $characterId, int $wounds, int $sourceId = 0)
    {
        $event = $this->theah->createEvent(Events::CharacterWounded);
        if ($event instanceof EventCharacterWounded)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = 'Debug Wound';
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_UnequipAttachment(int $playerId, int $characterId, int $attachmentId)
    {
        $event = $this->theah->createEvent(Events::AttachmentUnequipped);
        if ($event instanceof EventAttachmentUnequipped)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->attachmentId = $attachmentId;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_PlayApproachCharacterAtHome(int $playerId, int $characterId)
    {
        $event = EventFactory::createApproachCharacterPlayedEvent($playerId, $characterId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($debug = true);
    }

    public function debug_ClaimLocation(int $playerId, string $location)
    {
        $this->setControllerForLocation($location, $playerId);

        $claimEvent = EventFactory::createLocationClaimedEvent($playerId, 0, $location);
        $this->theah->eventCheck($claimEvent);
        $this->theah->queueEvent($claimEvent);
        $this->theah->runEvents($debug = true);
    }
}