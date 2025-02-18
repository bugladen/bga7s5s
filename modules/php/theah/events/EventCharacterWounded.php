<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventCharacterWounded extends Event
{
    public Character $character;
    public int $wounds;
    public string $reason;

    public function __construct()
    {
        parent::__construct();

        $this->wounds = 0;
    }
}