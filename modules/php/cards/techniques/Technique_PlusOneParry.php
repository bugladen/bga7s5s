<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_PlusOneParry extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Parry";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}