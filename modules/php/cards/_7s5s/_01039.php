<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _01039 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Philip Hase";
        $this->Image = "img/cards/7s5s/039.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 39;

        $this->Faction = "Eisen";
        $this->Title = "Grim Trapper";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Academic",
            "Hunter",
            "Eisen",
        ];
    }

}