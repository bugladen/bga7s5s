<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventSchemeCardRevealed extends Event
{
    public Scheme $scheme;
    public string $playerId;
    public string $location;
    public string $playerName;

    public function __construct()
    {
        parent::__construct();
    }
}