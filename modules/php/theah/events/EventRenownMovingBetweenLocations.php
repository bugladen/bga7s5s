<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventRenownMovingBetweenLocations extends Event
{
    public int $playerId;
    public string $fromLocation;
    public string $toLocation;
    public int $amount;
    public string $description;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->fromLocation = "";
        $this->toLocation = "";
        $this->amount = 0;
        $this->description = "";
    }
}