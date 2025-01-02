<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01062 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Odette Dubois D'Arrent";
        $this->Image = "img/cards/7s5s/062.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 62;

        $this->Faction = "Montaigne";
        $this->Title = "Genteel Spy";
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 4;
        $this->Influence = 3;
        $this->CrewCap = 6;
        $this->Panache = 7;

        $this->resetModifiedCharacterStats();
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Hero",
            "Diplomat",
            "Montaigne",
        ];
    }

}