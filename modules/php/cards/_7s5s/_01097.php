<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01097 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Sanjay";
        $this->Image = "img/cards/7s5s/097.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 97;

        $this->Faction = "Castille";
        $this->Title = "Loyal Merrymaker";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 1;        
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->Traits = [
            "Pirate",
            "Aragosta",
        ];
    }
}