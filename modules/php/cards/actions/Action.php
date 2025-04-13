<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Action 
{
    use CardAbilityTrait;

    public function __construct()
    {
        $this->initializeAbility();
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $this->Used = true;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return ! $this->Used;
    }

    public function getCharactersForAction(int $playerId, Theah $theah): array
    {
        return [];
    }
}