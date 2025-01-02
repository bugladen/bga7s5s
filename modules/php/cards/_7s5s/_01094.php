<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01094 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "'Padre' Anibal";
        $this->Image = "img/cards/7s5s/094.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 94;

        $this->Faction = "Castille";
        $this->Title = "Handyman and Husband";
        $this->Resolve = 5;
        $this->Combat = 0;
        $this->Finesse = 1;       
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->Traits = [
            "Academic",
            "Castille",
        ];
    }
}