<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class EventSchemeCardPlayed extends Event
{
    public Scheme $scheme;
    public string $playerId;
    public string $location;
    public string $playerName;

    public function __construct(Theah $theah)
    {
        parent::__construct($theah);
    }
}