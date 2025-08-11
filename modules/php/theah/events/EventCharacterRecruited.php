<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterRecruited extends Event
{
    public int $playerId;
    public int $characterId;
    public int $discount;
    public int $cost;

    public function __construct()
    {
        parent::__construct();
    }
}