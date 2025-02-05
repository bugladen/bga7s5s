<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01036 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Daniella Dietrich: Move";
        $this->ResetOnDuelEnd = false;
        $this->ResetOnDayEnd = true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}