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

use Bga\GameFramework\Actions\Debug;
use Bga\GameFramework\UserException;
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
        $card = $this->createCardInLocation($className, Game::LOCATION_HAND, $playerId, $playerId);

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
            $card = $this->createCardInLocation($className, Game::LOCATION_CITY_DECK, 0, 0);
            $this->cards->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, true);
        }
    }

    public function debug_AddCardToTopOfFactionDeck(string $className, int $playerId)
    {
        $card = $this->instantiateCard($className);
        if ($card) 
        {
            $deckName = $this->getPlayerFactionDeckName($playerId);
            $card = $this->createCardInLocation($className, $deckName, $playerId, $playerId);
            $this->cards->insertCardOnExtremePosition($card->Id, $deckName, true);
        }
    }

    #[Debug(reload: true)] 
    public function debug_SetCardInPlayerDiscardPile(string $className, int $playerId)
    {
        $location = $this->getPlayerDiscardDeckName($playerId);
        $this->createCardInLocation($className, $location, $playerId, $playerId);
    }

    #[Debug(reload: true)] 
    public function debug_SetCardInPlayerLocker(string $className, int $playerId)
    {
        $location = $this->getPlayerLockerName($playerId);
        $this->createCardInLocation($className, $location, $playerId, $playerId);
    }

    #[Debug(reload: true)] 
    public function debug_SetCardInCityDiscardPile(string $className)
    {
        $this->createCardInLocation($className, Game::LOCATION_CITY_DISCARD, 0, 0);
    }

    #[Debug(reload: true)] 
    public function debug_RecruitMercenary(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new UserException(clienttranslate("Card not found"));

        $recruitCharacterEvent = $this->theah->createEvent(Events::CharacterRecruited);
        if ($recruitCharacterEvent instanceof EventCharacterRecruited) {
            $recruitCharacterEvent->characterId = $cardId;
            $recruitCharacterEvent->playerId = $playerId;
            $recruitCharacterEvent->discount = 0;
            $recruitCharacterEvent->cost = 0;
        }
        $this->theah->queueEvent($recruitCharacterEvent);
        $this->theah->runEvents($skipTransitions = true);
    }

    public function debug_EngageCard(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new UserException(clienttranslate("Card not found"));

        $event = EventFactory::createCardEngagedEvent($playerId, $cardId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
    }

    public function debug_EngardeCard(int $cardId, int $playerId)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new UserException(clienttranslate("Card not found"));

        $event = EventFactory::createCardEngardedEvent($playerId, $cardId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
    }

    #[Debug(reload: true)] 
    public function debug_SetReknownOnCard(int $cardId, int $reknown)
    {
        $this->theah->buildCity();
        $card = $this->theah->getCardById($cardId);
        if ($card == null)
            throw new UserException(clienttranslate("Card not found"));

        $card->Reknown = $reknown;
        $this->updateCardObjectInDb($card);
    }

    public function debug_SetDay(int $day)
    {
        $this->setGameStateValue(Game::DAY, $day);
    }

    #[Debug(reload: true)] 
    public function debug_SetPlayerReknown(int $score, int $playerId)
    {
        $db = $this->theah->getDBObject();
        $db->setPlayerReknown($playerId, $score);

        // Notify players that the player has lost reknown
        $this->notify->all("playerReknownUpdated", clienttranslate('DEBUG: ${player_name} Renown now at ${total}.'), [
            "player_id" => $playerId,
            "player_name" => $this->getPlayerNameById($playerId),
            "total" => $score,
        ]);

    }

    #[Debug(reload: true)] 
    public function debug_AddReknownToLocation(string $location, int $amount)
    {
        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation)
        {
            $event->location = $location;
            $event->amount = $amount;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
    }

    #[Debug(reload: true)] 
    public function debug_RemoveReknownFromLocation(string $location, int $amount)
    {
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation)
        {
            $event->location = $location;
            $event->amount = $amount;
        }
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
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
        $this->theah->runEvents($skipTransitions = true);
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
        $this->theah->runEvents($skipTransitions = true);
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
        $this->theah->runEvents($skipTransitions = true);
    }

    public function debug_PlayApproachCharacterAtHome(int $playerId, int $characterId)
    {
        $event = EventFactory::createApproachCharacterPlayedEvent($playerId, $characterId);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
    }

    #[Debug(reload: true)]
    public function debug_ClaimLocation(string $location, int $playerId)
    {
        $this->setControllerForLocation($location, $playerId);

        $claimEvent = EventFactory::createLocationClaimedEvent($playerId, 0, $location);
        $this->theah->eventCheck($claimEvent);
        $this->theah->queueEvent($claimEvent);
        $this->theah->runEvents($skipTransitions = true);
    }

    #[Debug(reload: true)]
    public function debug_SetLocationCanBeClaimed(string $location, bool $canBeClaimed)
    {
        $this->theah->buildCity();
        $this->setCanBeClaimedForLocation($location, $canBeClaimed);
        $this->theah->getCityLocation($location)->CanBeClaimed = $canBeClaimed;
    }

    #[Debug(reload: true)]
    public function debug_SetLocationCanBecomeUncontrolled(string $location, bool $canBeUncontrolled)
    {
        $this->theah->buildCity();
        $this->setCanBecomeUncontrolledForLocation($location, $canBeUncontrolled);
        $this->theah->getCityLocation($location)->CanBecomeUncontrolled = $canBeUncontrolled;
    }

    public function debug_EmptyHand(int $playerId)
    {
        $hand = $this->cards->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        foreach ($hand as $card)
        {
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($playerId, $card['id'], false, false);
            $this->theah->queueEvent($discardEvent);
        }
        $this->theah->runEvents($skipTransitions = true);
    }

    public function debug_SetFirstPlayer(int $playerId)
    {
        $this->globals->set(Game::FIRST_PLAYER, $playerId);
        $turnOrders = $this->setNewPlayerOrder($playerId);

        // Notify all players of the first player.
        $this->notifyAllPlayers("firstPlayer", clienttranslate('DEBUG: ${player_name} is now the First Player.'), [
            'player_name' => $this->getPlayerNameById($playerId),
            'playerId' => $playerId,
            'turnOrders' => $turnOrders,
        ]);    
        $event = $this->theah->createEvent(Events::FirstPlayerDetermined);
        $this->theah->queueEvent($event);
        $this->theah->runEvents($skipTransitions = true);
    }
}