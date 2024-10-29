<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01093 extends Character
{
    public function __construct()
    {
        $this->Name = "Maya De La Rioja";
        $this->Image = "img/cards/7s5s/093.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 93;

        $this->Faction = "Castille";
        $this->Title = "Amoral Compass";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;       
        $this->Influence = 1;

        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;

        $this->Traits = [
            "Duelist",
            "Pirate",
            "Castille",
        ];
    }
}