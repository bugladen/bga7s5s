<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _01089 extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Soline El Gato";
        $this->Image = "img/cards/7s5s/089.jpg";
        $this->ExpansionName = "_7";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 89;

        $this->Faction = "Castille";
        $this->Title = "Prince of Thieves";
        $this->Resolve = 7;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;
        $this->CrewCap = 6;
        $this->Panache = 6;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;

        $this->Traits = [
            "Leader",
            "Pirate",
            "Scoundrel",
            "Castille",
        ];
    }

}