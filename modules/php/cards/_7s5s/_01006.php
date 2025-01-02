<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01006 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Don Contanzo Scarpa";
        $this->Image = "img/cards/7s5s/006.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 6;

        $this->Faction = "Vodacce";
        $this->Title = "Unrepentant Patriarch";
        $this->Resolve = 7;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 3;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->resetModifiedCharacterStats();
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Villain",
            "Red Hand",
            "Vodacce",
        ];
    }

}