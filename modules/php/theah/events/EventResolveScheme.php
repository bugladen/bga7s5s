<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventResolveScheme extends Event
{
    public Scheme $scheme;
    public int $playerId;
    public string $playerName;

    public function __construct()
    {
        parent::__construct();
    }
}