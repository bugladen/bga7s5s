<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01036 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move to Adjacent Location");
        $this->ResetOnDuelEnd = false;
        $this->ResetOnDayEnd = true;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}