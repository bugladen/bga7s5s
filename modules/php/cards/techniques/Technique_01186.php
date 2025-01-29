<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01186 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Marayam Benu Pleroma: No Adversary Maneuvers";
    }

    public function handleEvent(Card $owner, Event $event)
    { 
        parent::handleEvent($owner, $event);
    }
}