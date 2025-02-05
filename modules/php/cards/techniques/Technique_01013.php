<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01013 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Vissenta Scarpa: Add Thrust or Parry";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}