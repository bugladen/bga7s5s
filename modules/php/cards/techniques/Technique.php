<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

abstract class Technique 
{
    use CardAbilityTrait;

    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

    public function __construct()
    {
        $this->initializeAbility();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventDuskEndOfDay && $this->ResetOnDayEnd)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd && $this->ResetOnDuelEnd)
        {
            $this->Used = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }
}