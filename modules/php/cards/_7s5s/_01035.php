<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01035 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaspar Dietrich";
        $this->Image = "img/cards/7s5s/035.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 35;

        $this->Faction = "Eisen";
        $this->Title = "Old Iron";
        $this->Resolve = 9;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->resetModifiedCharacterStats();
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Hero",
            "General",
            "Eisen",
        ];
    }

    public function getParleyDiscount(bool $parleying) : int
    {
        return parent::getParleyDiscount($parleying) + 2;
    }

}