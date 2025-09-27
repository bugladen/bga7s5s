<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventLocationPressured extends Event
{
    public int $playerId;
    public int $performerId;
    public string $location;
    public string $pressureType;
    public string $totalsExplanation;
    public bool $success;
    public int $difference;
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
        $this->difference = 0;
        $this->highDramaBasicAction = false;
        $this->abilityId = "";

        $this->runEventHubAfterCards = true;
    }
}