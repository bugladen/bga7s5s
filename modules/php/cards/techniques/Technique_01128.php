<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01128 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Mireli Sabre: +1 Parry or Thrust";
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);
    }
}