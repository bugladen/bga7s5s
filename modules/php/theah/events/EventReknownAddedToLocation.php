<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventReknownAddedToLocation extends Event
{
    public int $playerId;
    public string $location;
    public int $amount;
    public string $description;
    public bool $isMove;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->location = "";
        $this->amount = 0;
        $this->description = "";
        $this->isMove = false;

    }
}