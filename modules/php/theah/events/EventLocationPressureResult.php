<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventLocationPressureResult extends Event
{
    public int $playerId;
    public int $performerId;
    public string $location;
    public string $pressureType;
    public string $totalsExplanation;
    public bool $success;
    public bool $highDramaBasicAction;
    public string $abilityId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->performerId = 0;
        $this->location = "";
        $this->pressureType = "";
        $this->totalsExplanation = "";
        $this->success = false;
        $this->highDramaBasicAction = false;
        $this->abilityId = "";
    }
}