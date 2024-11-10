<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventPhaseMorning extends Event
{
    public function __construct(Theah $theah)
    {
        parent::__construct($theah);
    }
}