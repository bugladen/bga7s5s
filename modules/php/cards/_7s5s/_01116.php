<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01116 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Yevgeni";
        $this->Image = "img/cards/7s5s/116.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 116;

        $this->Faction = "Usurra";
        $this->Title = "The Boar";
        $this->Resolve = 12;
        $this->Combat = 4;
        $this->Finesse = 2;
        $this->Influence = 1;
        $this->CrewCap = 5;
        $this->Panache = 5;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Exile",
            "Hero",
            "Sorcerer",
            "Usurra",
        ];
    }

}