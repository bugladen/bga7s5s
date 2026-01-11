<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01119 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Nazem ibn Umur");
        $this->Image = "01119.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 119;

        $this->initializeFaction("Ussura");
        $this->Title = "Truly Fearless";
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 0;

        $this->Traits = [
            "Duelist",
            "Anatol Ayh"
        ];

        $this->resetCard();
    }

    private function updateInfluence(Theah $theah, int $count = 0)
    {

        $influenceEvent = EventFactory::createCharacterInfluenceModifiedEvent(
            $this->ControllerId, 
            $this->Id, 
            $this->ModifiedInfluence, 
            $count, 
            $this->getInjectCode()
        );
        $theah->queueEvent($influenceEvent);
    }

    public function getOpposingEngagedCharacterCount(Theah $theah, string $location): int
    {
        $characters = $theah->getCharactersAtLocation($location);
        $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($this->ControllerId) && $character->Engaged);
        return count($characters);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);


        if ($event instanceof EventCardMoved && $event->cardId == $this->Id)
        {
            $count = $this->getOpposingEngagedCharacterCount($event->theah, $event->toLocation);
            $this->updateInfluence($event->theah, $count);
        }

        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->toLocation == $this->Location)
        {
            $count = $this->getOpposingEngagedCharacterCount($event->theah, $event->toLocation);
            $this->updateInfluence($event->theah, $count);
        }
        
        if ($event instanceof EventCardMoved && $event->cardId != $this->Id && $event->fromLocation == $this->Location)
        {
            $count = $this->getOpposingEngagedCharacterCount($event->theah, $event->fromLocation);
            $this->updateInfluence($event->theah, $count);
        }

        if ($event instanceof EventCharacterMustered && $event->characterId == $this->Id && $event->location == $this->Location)
        {
            $count = $this->getOpposingEngagedCharacterCount($event->theah, $event->location);
            $this->updateInfluence($event->theah, $count);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId != $this->Id)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character->Location == $this->Location)
            {
                $count = $this->getOpposingEngagedCharacterCount($event->theah, $this->Location);
                $this->updateInfluence($event->theah, $count);
            }
        }

        if ($event instanceof EventCardEngaged)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character->ControllerId != $this->ControllerId && $character->Location == $this->Location)
            {
                $count = $this->getOpposingEngagedCharacterCount($event->theah, $character->Location) + 1;
                $this->updateInfluence($event->theah, $count);
            }
        }

        if ($event instanceof EventCardEngarded)
        {
            $character = $event->theah->getCharacterById($event->cardId);
            if ($character->ControllerId != $this->ControllerId && $character->Location == $this->Location)
            {
                $count = $this->getOpposingEngagedCharacterCount($event->theah, $character->Location) - 1;
                $this->updateInfluence($event->theah, $count);
            }
        }

        if ($event instanceof EventChallengeRejected && $event->challengerId == $this->Id)
        {
            $character = $event->theah->getCharacterById($event->targetId);
            if (!$character->Engaged)
            {
                $game = $event->theah->game;
                $game->notify->all("message", clienttranslate('${player_name} has rejected a challenge from ${challenger_inject_code}. ${target_inject_code} will be Engaged.'), [
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "challenger_inject_code" => $this->getInjectCode(),
                    "target_inject_code" => $character->getInjectCode(),
                ]);
                $engageEvent = EventFactory::createCardEngagedEvent($this->ControllerId, $event->targetId);
                $event->theah->queueEvent($engageEvent);
            }
        }    
    }
}