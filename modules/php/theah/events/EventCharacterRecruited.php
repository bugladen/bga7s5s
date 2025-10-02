<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterRecruited extends Event
{
    public int $playerId;
    public int $characterId;
    public int $discount;
    public string $explanations;
    public int $cost;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->characterId = 0;
        $this->discount = 0;
        $this->explanations = '';
        $this->cost = 0;
    }
}