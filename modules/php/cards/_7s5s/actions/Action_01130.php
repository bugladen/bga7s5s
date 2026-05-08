<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01130 extends RiskAction
{
    public bool $IsActive = false;
    public int $ControllingCharacterId = 0;
    public string $ControlledLocation = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Claim Location";
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        foreach ($performers as $performer)
        {
            $characters = $theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $playerId);
            $location = $theah->getCityLocation($performer->Location);
            if (count($characters) == 1 && ! $location->isControlled())
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $characters = $theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $playerId);
            $location = $theah->getCityLocation($performer->Location);
            if (count($characters) == 1 && ! $location->isControlled())
            {
                $availablePerformers[] = $performer;
            }
        }

        return $availablePerformers;
    }

    private function setConditionEnded(Game $game)
    {
        $character = $game->theah->getCharacterById($this->ControllingCharacterId);
        $character->removeCondition(Game::INDOMITABLE_WILL_CONDITION);

        $game->notify->all("indomitableWillConditionEnded", '${character_inject_code} has lost Indomitable Will.', [
            "character_inject_code" => $character->getInjectCode(),
            "cardId" => $this->ControllingCharacterId,
        ]);

        $locationUncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent($character->ControllerId, $this->ControlledLocation);
        $game->theah->queueEvent($locationUncontrolledEvent);

        $this->IsActive = false;
        $this->ControllingCharacterId = 0;
        $this->ControlledLocation = "";
        $owner = $this->getOwningCard($game->theah);
        $owner->IsUpdated = true;

    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventLocationClaimed && $this->IsActive)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($event->playerId != $owner->ControllerId && $event->location == $this->ControlledLocation)
            {
                $game = $event->theah->game;    
                $owner = $this->getOwningCard($event->theah);
                $character = $event->theah->getCharacterById($this->ControllingCharacterId);
                throw new \BgaUserException(sprintf($game->translate("%s: %s is still at the location. Location cannot be claimed."), $owner->Name, $character->Name));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            $this->IsActive = true;
            $this->ControllingCharacterId = $performer->Id;
            $this->ControlledLocation = $performer->Location;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $performer->addCondition(Game::INDOMITABLE_WILL_CONDITION);

            $claimEvent = EventFactory::createLocationClaimedEvent($performer->ControllerId, $performerId, $performer->Location);
            $event->theah->queueEvent($claimEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);

            $game->notify->all("indomitableWillConditionStarted", '${character_inject_code} has gained Indomitable Will.', [
                "character_inject_code" => $performer->getInjectCode(),
                "cardId" => $performer->Id,
            ]);
       }

       if ($event instanceof EventCardMoved && $this->IsActive)
       {
            if ($event->cardId == $this->ControllingCharacterId && $event->toLocation != $this->ControlledLocation)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventCardDiscardedFromPlay && $this->IsActive)
       {
            if ($event->cardId == $this->ControllingCharacterId)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventCharacterDestroyed && $this->IsActive)
       {
            if ($event->characterId == $this->ControllingCharacterId)
            {
                $this->setConditionEnded($event->theah->game);
            }
       }

       if ($event instanceof EventDuskEndOfDay && $this->IsActive)
       {
            $this->setConditionEnded($event->theah->game);
       }
    }
}