<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;

trait DebugTrait
{
    public function debug_AddCardToHand(string $className, int $playerId)
    {
        $card = $this->createCardInLocation($className, Game::LOCATION_HAND, $playerId);

        $this->notifyPlayer($playerId, "drawCard", 'Debug Draw', [
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
            $card = $this->createCardInLocation($className, Game::LOCATION_CITY_DECK, 0);
            $this->cards->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, true);
        }
    }

    public function debug_SetCardInPlayerDiscardPile(string $className, int $playerId)
    {
        $location = $this->getPlayerDiscardDeckName($playerId);
        $this->createCardInLocation($className, $location, $playerId);
    }

    public function debug_SetCardInCityDiscardPile(string $className)
    {
        $this->createCardInLocation($className, Game::LOCATION_CITY_DISCARD, 0);
    }

    public function debug_RecruitMercenary(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new \BgaUserException(self::_("Card not found"));

        $recruitCharacterEvent = $this->theah->createEvent(Events::CharacterRecruited);
        if ($recruitCharacterEvent instanceof EventCharacterRecruited) {
            $recruitCharacterEvent->characterId = $cardId;
            $recruitCharacterEvent->playerId = $playerId;
            $recruitCharacterEvent->discount = 0;
            $recruitCharacterEvent->cost = 0;
        }
        $this->theah->queueEvent($recruitCharacterEvent);
        $this->theah->runEvents($debug = true);
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

    public function debug_HealCharacter(int $characterId, int $wounds, int $sourceId = 0)
    {
        $event = $this->theah->createEvent(Events::CharacterHealed);
        if ($event instanceof EventCharacterHealed)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = 'Debug Heal';
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

    public function debug_ClaimLocation(string $location, int $playerId)
    {
        $this->setControllerForLocation($location, $playerId);

        $claimEvent = EventFactory::createLocationClaimedEvent($playerId, 0, $location);
        $this->theah->eventCheck($claimEvent);
        $this->theah->queueEvent($claimEvent);
        $this->theah->runEvents($debug = true);
    }

    public function debug_EmptyHand(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        foreach ($hand as $card)
        {
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($playerId, $card['id'], false, false);
            $this->theah->queueEvent($discardEvent);
        }
        $this->theah->runEvents($debug = true);
    }
}