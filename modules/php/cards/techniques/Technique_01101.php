<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01101 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Gallegos Blade: -1 Parry";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }

}