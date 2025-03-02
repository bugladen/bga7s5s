<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

abstract class Technique extends CardAbility
{
    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

    public function __construct()
    {
        parent::__construct();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

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