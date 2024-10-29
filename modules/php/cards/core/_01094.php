<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01094 extends Character
{
    public function __construct()
    {
        $this->Name = "'Padre' Anibal";
        $this->Image = "img/cards/7s5s/094.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 94;

        $this->Faction = "Castille";
        $this->Title = "Handyman and Husband";
        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 1;       
        $this->Influence = 2;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->Traits = [
            "Academic",
            "Castille",
        ];
    }
}