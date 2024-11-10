<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventNewDay extends Event
{
    public int $dayNumber;

    public function __construct(Theah $theah)
    {
        parent::__construct($theah);
    }
}