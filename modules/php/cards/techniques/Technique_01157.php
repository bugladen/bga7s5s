<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01157 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "+1 Thrust";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}