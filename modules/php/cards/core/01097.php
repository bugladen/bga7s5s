<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\core;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01097 extends Character
{
    public function __construct()
    {
        $this->Name = "Sanjay";
        $this->Image = "img/cards/core/097.jpg";
        $this->ExpansionName = "Core";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 97;

        $this->Faction = "Castille";
        $this->Title = "Loyal Merrymaker";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 1;        
        $this->Influence = 2;

        $this->Traits = [
            "Pirate",
            "Aragosta",
        ];
    }
}