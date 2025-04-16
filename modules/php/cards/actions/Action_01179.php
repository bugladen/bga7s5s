<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01179 extends EventCityAction
{
    private array $playersUsed = [];
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Siren's Scream: Take a Reknown";
    }

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        $characters = parent::getCharactersForAction($playerId, $theah);
        $characters = array_filter($characters, fn($character) => !$character->Engaged);

        return $characters;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $card = $this->getOwningCard($theah);
        if ($card->Reknown == 0)
        {
            return false;
        }

        // Check if player has used this Action today
        if (in_array($playerId, $this->playersUsed))
        {
            return false;
        }

        return true;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        //Check to see if player has already used this Action today
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            if (in_array($event->playerId, $this->playersUsed))
            {
                throw new \BgaUserException(_("You have already used this Action today."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //Clear players used list at the end of the day
        if ($event instanceof EventDuskEndOfDay)
        {
            $this->playersUsed = [];
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }

        // Take Reknown action
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->playersUsed[] = $event->playerId;

            $engageEvent = $event->theah->createEvent(Events::CardEngaged);
            $engageEvent = EventFactory::createCardEngagedEvent($event->playerId, $event->performerId);
            $event->theah->queueEvent($engageEvent);

            $reknownEvent = $event->theah->createEvent(Events::ReknownRemovedFromCard);
            if ($reknownEvent instanceof EventReknownRemovedFromCard)
            {
                $reknownEvent->playerId = $event->playerId;
                $reknownEvent->cardId = $this->OwnerId;
                $reknownEvent->amount = 1;
            }
            $event->theah->queueEvent($reknownEvent);

            $playerEvent = $event->theah->createEvent(Events::PlayerGainsReknown);
            if ($playerEvent instanceof EventPlayerGainsReknown)
            {
                $playerEvent->playerId = $event->playerId;
                $playerEvent->amount = 1;
            }
            $event->theah->queueEvent($playerEvent);
        }
    }

}