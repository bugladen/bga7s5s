<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

// This event should be queued first before any other events are queued so that they can be canceled by reactions.
// Use cases:
//    Torsten Vakt can cancel Sorceries and Sorcerer Abilities Targeting him.
class EventSorcererAbilityStart extends Event
{
    public int $playerId;
    public int $sourceId;
    public string $abilityId;
    public int $performerId;
    public int $targetId;
    public string $targetLocation;

    public function __construct()
    {
        parent::__construct();
        $this->playerId = 0;
        $this->sourceId = 0;
        $this->abilityId = "";
        $this->performerId = 0;
        $this->targetId = 0;
        $this->targetLocation = "";
    }
}