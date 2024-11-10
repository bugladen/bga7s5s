<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardPlayed;

abstract class Leader extends Character
{

    public int $CrewCap;
    public int $ModifiedCrewCap;
    public int $Panache;
    public int $ModifiedPanache;

    public function __construct(){
        parent::__construct();

        $this->CrewCap = 0;
        $this->ModifiedCrewCap = 0;
        $this->Panache = 0;
        $this->ModifiedPanache = 0;
    }

    public function handleEvent($event)
    {
        if ($event instanceof EventSchemeCardPlayed) {
            $this->ModifiedPanache += $event->scheme->PanacheModifier;
        }

        return $event;
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add leader specific properties
        $properties['crewCap'] = $this->CrewCap;
        $properties['modifiedCrewCap'] = $this->ModifiedCrewCap;
        $properties['panache'] = $this->Panache;
        $properties['modifiedPanache'] = $this->ModifiedPanache;

        $properties['type'] = 'Leader';

        return $properties;
    }

}