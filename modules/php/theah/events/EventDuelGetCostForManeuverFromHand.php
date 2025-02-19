<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelGetCostForManeuverFromHand extends Event
{
    public int $actorId;
    public int $adversaryId;
    public int $combatCardId;
    public string $maneuverId;
    public int $cost;
    public int $discount;
    public Array $explanations;    

    public function __construct()
    {
        parent::__construct();

        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->combatCardId = 0;
        $this->maneuverId = '';
        $this->cost = 0;
        $this->discount = 0;
        $this->explanations = [];
        $this->runHandlerAfterCards = true;
    }
}