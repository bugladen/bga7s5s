<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01093 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Maya de La Rioja: -1 Riposte";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}