<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class Technique_01049 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Polished Flintlock: Gain Lethal";
    }

    public function handleEvent(Card $owner, Event $event)
    { 
        parent::handleEvent($owner, $event);
    }
}